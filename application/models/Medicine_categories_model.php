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
 * Medicine categories model.
 *
 * Handles all the database operations of the medicine category resource.
 * Uses raw table name 'medicine_categories' (no ea_ prefix).
 *
 * @package Models
 */
class Medicine_categories_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'nameEn' => 'name_en',
        'nameAr' => 'name_ar',
        'description' => 'description',
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
     * Save (insert or update) a medicine category.
     *
     * @param array $category Associative array with the category data.
     *
     * @return int Returns the category ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $category): int
    {
        $this->validate($category);

        if (empty($category['id'])) {
            return $this->insert($category);
        } else {
            return $this->update($category);
        }
    }

    /**
     * Validate the category data.
     *
     * @param array $category Associative array with the category data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $category): void
    {
        if (!empty($category['id'])) {
            $count = $this->without_prefix(
                fn() => $this->db->get_where('medicine_categories', ['id' => $category['id']])->num_rows(),
            );

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided medicine category ID does not exist in the database: ' . $category['id'],
                );
            }
        }

        if (empty($category['id']) && empty($category['name_en'])) {
            throw new InvalidArgumentException('The name_en field is required.');
        }
    }

    /**
     * Insert a new medicine category into the database.
     *
     * @param array $category Associative array with the category data.
     *
     * @return int Returns the category ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $category): int
    {
        $category['create_datetime'] = date('Y-m-d H:i:s');
        $category['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($category) {
            if (!$this->db->insert('medicine_categories', $category)) {
                throw new RuntimeException('Could not insert medicine category.');
            }

            return $this->db->insert_id();
        });
    }

    /**
     * Update an existing medicine category.
     *
     * @param array $category Associative array with the category data.
     *
     * @return int Returns the category ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $category): int
    {
        $category['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($category) {
            if (!$this->db->update('medicine_categories', $category, ['id' => $category['id']])) {
                throw new RuntimeException('Could not update medicine category.');
            }

            return $category['id'];
        });
    }

    /**
     * Remove an existing medicine category from the database.
     *
     * @param int $category_id Category ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $category_id): void
    {
        $this->without_prefix(fn() => $this->db->delete('medicine_categories', ['id' => $category_id]));
    }

    /**
     * Get a specific medicine category from the database.
     *
     * @param int $category_id The ID of the record to be returned.
     *
     * @return array Returns an array with the category data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $category_id): array
    {
        $category = $this->without_prefix(
            fn() => $this->db->get_where('medicine_categories', ['id' => $category_id])->row_array(),
        );

        if (!$category) {
            throw new InvalidArgumentException(
                'The provided medicine category ID was not found in the database: ' . $category_id,
            );
        }

        $this->cast($category);

        return $category;
    }

    /**
     * Get all medicine categories that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of medicine categories.
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

            $categories = $this->db->get('medicine_categories', $limit, $offset)->result_array();

            foreach ($categories as &$category) {
                $this->cast($category);
            }

            return $categories;
        });
    }

    /**
     * Search medicine categories by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of medicine categories.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        return $this->without_prefix(function () use ($keyword, $limit, $offset, $order_by) {
            $categories = $this->db
                ->select()
                ->from('medicine_categories')
                ->group_start()
                ->like('name_en', $keyword)
                ->or_like('name_ar', $keyword)
                ->group_end()
                ->limit($limit)
                ->offset($offset)
                ->order_by($order_by ?? 'name_en')
                ->get()
                ->result_array();

            foreach ($categories as &$category) {
                $this->cast($category);
            }

            return $categories;
        });
    }

    /**
     * Convert the database record to the equivalent API resource.
     *
     * @param array $category Category data.
     */
    public function api_encode(array &$category): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $category) ? (int) $category['id'] : null,
            'nameEn' => $category['name_en'] ?? null,
            'nameAr' => $category['name_ar'] ?? null,
            'description' => $category['description'] ?? null,
            'isActive' => (bool) ($category['is_active'] ?? true),
        ];

        $category = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database record.
     *
     * @param array $category API resource.
     * @param array|null $base Base data to be overwritten.
     */
    public function api_decode(array &$category, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $category)) {
            $decoded_resource['id'] = $category['id'];
        }

        if (array_key_exists('nameEn', $category)) {
            $decoded_resource['name_en'] = $category['nameEn'];
        }

        if (array_key_exists('nameAr', $category)) {
            $decoded_resource['name_ar'] = $category['nameAr'];
        }

        if (array_key_exists('description', $category)) {
            $decoded_resource['description'] = $category['description'];
        }

        if (array_key_exists('isActive', $category)) {
            $decoded_resource['is_active'] = (bool) $category['isActive'] ? 1 : 0;
        }

        $category = $decoded_resource;
    }
}
