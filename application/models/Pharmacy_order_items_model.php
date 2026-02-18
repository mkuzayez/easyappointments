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
 * Pharmacy order items model.
 *
 * Handles all the database operations of the pharmacy order item resource.
 * Uses raw table name 'pharmacy_order_items' (no ea_ prefix).
 *
 * @package Models
 */
class Pharmacy_order_items_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'id_pharmacy_orders' => 'integer',
        'id_medicines' => 'integer',
        'medicine_price' => 'float',
        'quantity' => 'integer',
        'line_total' => 'float',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'pharmacyOrderId' => 'id_pharmacy_orders',
        'medicineId' => 'id_medicines',
        'medicineNameEn' => 'medicine_name_en',
        'medicineNameAr' => 'medicine_name_ar',
        'medicinePrice' => 'medicine_price',
        'medicineUnit' => 'medicine_unit',
        'quantity' => 'quantity',
        'dosageNotes' => 'dosage_notes',
        'lineTotal' => 'line_total',
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
     * Save (insert or update) a pharmacy order item.
     *
     * @param array $item Associative array with the item data.
     *
     * @return int Returns the item ID.
     */
    public function save(array $item): int
    {
        if (empty($item['id'])) {
            return $this->insert($item);
        } else {
            return $this->update($item);
        }
    }

    /**
     * Insert a new pharmacy order item into the database.
     *
     * @param array $item Associative array with the item data.
     *
     * @return int Returns the item ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $item): int
    {
        $item['create_datetime'] = date('Y-m-d H:i:s');
        $item['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($item) {
            if (!$this->db->insert('pharmacy_order_items', $item)) {
                throw new RuntimeException('Could not insert pharmacy order item.');
            }

            return $this->db->insert_id();
        });
    }

    /**
     * Update an existing pharmacy order item.
     *
     * @param array $item Associative array with the item data.
     *
     * @return int Returns the item ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $item): int
    {
        $item['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($item) {
            if (!$this->db->update('pharmacy_order_items', $item, ['id' => $item['id']])) {
                throw new RuntimeException('Could not update pharmacy order item.');
            }

            return $item['id'];
        });
    }

    /**
     * Get a specific pharmacy order item from the database.
     *
     * @param int $item_id The ID of the record to be returned.
     *
     * @return array Returns an array with the item data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $item_id): array
    {
        $item = $this->without_prefix(
            fn() => $this->db->get_where('pharmacy_order_items', ['id' => $item_id])->row_array(),
        );

        if (!$item) {
            throw new InvalidArgumentException(
                'The provided pharmacy order item ID was not found in the database: ' . $item_id,
            );
        }

        $this->cast($item);

        return $item;
    }

    /**
     * Get all order items for a given pharmacy order.
     *
     * @param int $order_id Pharmacy order ID.
     *
     * @return array Returns an array of order items.
     */
    public function get_by_order(int $order_id): array
    {
        return $this->without_prefix(function () use ($order_id) {
            $items = $this->db
                ->get_where('pharmacy_order_items', ['id_pharmacy_orders' => $order_id])
                ->result_array();

            foreach ($items as &$item) {
                $this->cast($item);
            }

            return $items;
        });
    }

    /**
     * Get all pharmacy order items that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of pharmacy order items.
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

            $items = $this->db->get('pharmacy_order_items', $limit, $offset)->result_array();

            foreach ($items as &$item) {
                $this->cast($item);
            }

            return $items;
        });
    }

    /**
     * Convert the database record to the equivalent API resource.
     *
     * @param array $item Item data.
     */
    public function api_encode(array &$item): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $item) ? (int) $item['id'] : null,
            'pharmacyOrderId' => (int) ($item['id_pharmacy_orders'] ?? 0),
            'medicineId' => (int) ($item['id_medicines'] ?? 0),
            'medicineNameEn' => $item['medicine_name_en'] ?? null,
            'medicineNameAr' => $item['medicine_name_ar'] ?? null,
            'medicinePrice' => (float) ($item['medicine_price'] ?? 0),
            'medicineUnit' => $item['medicine_unit'] ?? null,
            'quantity' => (int) ($item['quantity'] ?? 1),
            'dosageNotes' => $item['dosage_notes'] ?? null,
            'lineTotal' => (float) ($item['line_total'] ?? 0),
        ];

        $item = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database record.
     *
     * @param array $item API resource.
     * @param array|null $base Base data to be overwritten.
     */
    public function api_decode(array &$item, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $item)) {
            $decoded_resource['id'] = $item['id'];
        }

        if (array_key_exists('pharmacyOrderId', $item)) {
            $decoded_resource['id_pharmacy_orders'] = $item['pharmacyOrderId'];
        }

        if (array_key_exists('medicineId', $item)) {
            $decoded_resource['id_medicines'] = $item['medicineId'];
        }

        if (array_key_exists('quantity', $item)) {
            $decoded_resource['quantity'] = (int) $item['quantity'];
        }

        if (array_key_exists('dosageNotes', $item)) {
            $decoded_resource['dosage_notes'] = $item['dosageNotes'];
        }

        $item = $decoded_resource;
    }
}
