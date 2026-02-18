<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Medicines model.
 *
 * Handles all the database operations of the medicine resource.
 * Uses raw table name 'medicines' (no ea_ prefix).
 *
 * @package Models
 */
class Medicines_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'id_medicine_categories' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'medicineCategoryId' => 'id_medicine_categories',
        'nameEn' => 'name_en',
        'nameAr' => 'name_ar',
        'description' => 'description',
        'price' => 'price',
        'unit' => 'unit',
        'stockStatus' => 'stock_status',
        'isActive' => 'is_active',
    ];

    /**
     * Run a callback with the db prefix temporarily removed.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    private function without_prefix(callable $callback): mixed
    {
        $original = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            return $callback();
        } finally {
            $this->db->dbprefix = $original;
        }
    }

    /**
     * Save (insert or update) a medicine.
     *
     * @param array $medicine Associative array with the medicine data.
     *
     * @return int Returns the medicine ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $medicine): int
    {
        $this->validate($medicine);

        if (empty($medicine['id'])) {
            return $this->insert($medicine);
        } else {
            return $this->update($medicine);
        }
    }

    /**
     * Validate the medicine data.
     *
     * @param array $medicine Associative array with the medicine data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $medicine): void
    {
        if (!empty($medicine['id'])) {
            $count = $this->without_prefix(
                fn() => $this->db->get_where('medicines', ['id' => $medicine['id']])->num_rows(),
            );

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided medicine ID does not exist in the database: ' . $medicine['id'],
                );
            }
        }

        if (empty($medicine['id'])) {
            if (empty($medicine['name_en'])) {
                throw new InvalidArgumentException('The name_en field is required.');
            }

            if (empty($medicine['id_medicine_categories'])) {
                throw new InvalidArgumentException('The medicine category ID is required.');
            }

            if (!isset($medicine['price']) || $medicine['price'] < 0) {
                throw new InvalidArgumentException('A valid price is required.');
            }
        }
    }

    /**
     * Insert a new medicine into the database.
     *
     * @param array $medicine Associative array with the medicine data.
     *
     * @return int Returns the medicine ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $medicine): int
    {
        $medicine['create_datetime'] = date('Y-m-d H:i:s');
        $medicine['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($medicine) {
            if (!$this->db->insert('medicines', $medicine)) {
                throw new RuntimeException('Could not insert medicine.');
            }

            return $this->db->insert_id();
        });
    }

    /**
     * Update an existing medicine.
     *
     * @param array $medicine Associative array with the medicine data.
     *
     * @return int Returns the medicine ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $medicine): int
    {
        $medicine['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($medicine) {
            if (!$this->db->update('medicines', $medicine, ['id' => $medicine['id']])) {
                throw new RuntimeException('Could not update medicine.');
            }

            return $medicine['id'];
        });
    }

    /**
     * Soft-delete a medicine by deactivating it.
     *
     * @param int $medicine_id Medicine ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $medicine_id): void
    {
        $this->without_prefix(
            fn() => $this->db->update(
                'medicines',
                ['is_active' => 0, 'update_datetime' => date('Y-m-d H:i:s')],
                ['id' => $medicine_id],
            ),
        );
    }

    /**
     * Get a specific medicine from the database.
     *
     * @param int $medicine_id The ID of the record to be returned.
     *
     * @return array Returns an array with the medicine data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $medicine_id): array
    {
        $medicine = $this->without_prefix(
            fn() => $this->db->get_where('medicines', ['id' => $medicine_id])->row_array(),
        );

        if (!$medicine) {
            throw new InvalidArgumentException(
                'The provided medicine ID was not found in the database: ' . $medicine_id,
            );
        }

        $this->cast($medicine);

        return $medicine;
    }

    /**
     * Get all medicines that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of medicines.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        return $this->without_prefix(function () use ($where, $limit, $offset, $order_by) {
            if ($where !== null) {
                $this->db->where($where);
            }

            if ($order_by !== null) {
                $this->db->order_by($order_by);
            }

            $medicines = $this->db->get('medicines', $limit, $offset)->result_array();

            foreach ($medicines as &$medicine) {
                $this->cast($medicine);
            }

            return $medicines;
        });
    }

    /**
     * Get active and in-stock medicines, optionally filtered by category.
     *
     * @param int|null $category_id Medicine category ID.
     *
     * @return array Returns an array of active, in-stock medicines.
     */
    public function get_active_in_stock(?int $category_id = null): array
    {
        return $this->without_prefix(function () use ($category_id) {
            $this->db->where('is_active', 1);
            $this->db->where('stock_status', 'in_stock');

            if ($category_id !== null) {
                $this->db->where('id_medicine_categories', $category_id);
            }

            $medicines = $this->db->get('medicines')->result_array();

            foreach ($medicines as &$medicine) {
                $this->cast($medicine);
            }

            return $medicines;
        });
    }

    /**
     * Search medicines by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of medicines.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        return $this->without_prefix(function () use ($keyword, $limit, $offset, $order_by) {
            $medicines = $this->db
                ->select()
                ->from('medicines')
                ->group_start()
                ->like('name_en', $keyword)
                ->or_like('name_ar', $keyword)
                ->or_like('description', $keyword)
                ->group_end()
                ->limit($limit)
                ->offset($offset)
                ->order_by($order_by ?? 'name_en')
                ->get()
                ->result_array();

            foreach ($medicines as &$medicine) {
                $this->cast($medicine);
            }

            return $medicines;
        });
    }

    /**
     * Load related resources to a medicine.
     *
     * @param array $medicine Associative array with the medicine data.
     * @param array $resources Resource names to be attached.
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$medicine, array $resources): void
    {
        if (empty($medicine) || empty($resources)) {
            return;
        }

        foreach ($resources as $resource) {
            match ($resource) {
                'category' => $medicine['category'] = $this->without_prefix(
                    fn() => $this->db
                        ->get_where('medicine_categories', ['id' => $medicine['id_medicine_categories']])
                        ->row_array(),
                ),
                default => throw new InvalidArgumentException(
                    'The requested medicine relation is not supported: ' . $resource,
                ),
            };
        }
    }

    /**
     * Convert the database record to the equivalent API resource.
     *
     * @param array $medicine Medicine data.
     */
    public function api_encode(array &$medicine): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $medicine) ? (int) $medicine['id'] : null,
            'medicineCategoryId' => (int) ($medicine['id_medicine_categories'] ?? 0),
            'nameEn' => $medicine['name_en'] ?? null,
            'nameAr' => $medicine['name_ar'] ?? null,
            'description' => $medicine['description'] ?? null,
            'price' => (float) ($medicine['price'] ?? 0),
            'unit' => $medicine['unit'] ?? 'tablet',
            'stockStatus' => $medicine['stock_status'] ?? 'in_stock',
            'isActive' => (bool) ($medicine['is_active'] ?? true),
        ];

        if (isset($medicine['category'])) {
            $encoded_resource['category'] = $medicine['category'];
        }

        $medicine = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database record.
     *
     * @param array $medicine API resource.
     * @param array|null $base Base data to be overwritten.
     */
    public function api_decode(array &$medicine, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $medicine)) {
            $decoded_resource['id'] = $medicine['id'];
        }

        if (array_key_exists('medicineCategoryId', $medicine)) {
            $decoded_resource['id_medicine_categories'] = $medicine['medicineCategoryId'];
        }

        if (array_key_exists('nameEn', $medicine)) {
            $decoded_resource['name_en'] = $medicine['nameEn'];
        }

        if (array_key_exists('nameAr', $medicine)) {
            $decoded_resource['name_ar'] = $medicine['nameAr'];
        }

        if (array_key_exists('description', $medicine)) {
            $decoded_resource['description'] = $medicine['description'];
        }

        if (array_key_exists('price', $medicine)) {
            $decoded_resource['price'] = (float) $medicine['price'];
        }

        if (array_key_exists('unit', $medicine)) {
            $decoded_resource['unit'] = $medicine['unit'];
        }

        if (array_key_exists('stockStatus', $medicine)) {
            $decoded_resource['stock_status'] = $medicine['stockStatus'];
        }

        if (array_key_exists('isActive', $medicine)) {
            $decoded_resource['is_active'] = (bool) $medicine['isActive'] ? 1 : 0;
        }

        $medicine = $decoded_resource;
    }
}
