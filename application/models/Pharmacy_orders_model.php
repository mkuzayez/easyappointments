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
 * Pharmacy orders model.
 *
 * Handles all the database operations of the pharmacy order resource.
 * Uses raw table name 'pharmacy_orders' (no ea_ prefix).
 *
 * @package Models
 */
class Pharmacy_orders_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'id_prescriptions' => 'integer',
        'id_branches' => 'integer',
        'subtotal' => 'float',
        'total' => 'float',
        'payment_amount' => 'float',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'hash' => 'hash',
        'prescriptionId' => 'id_prescriptions',
        'customerFirstName' => 'customer_first_name',
        'customerLastName' => 'customer_last_name',
        'customerEmail' => 'customer_email',
        'customerPhone' => 'customer_phone',
        'fulfillmentMethod' => 'fulfillment_method',
        'deliveryAddress' => 'delivery_address',
        'branchId' => 'id_branches',
        'status' => 'status',
        'subtotal' => 'subtotal',
        'total' => 'total',
        'currency' => 'currency',
        'stripeSessionId' => 'stripe_session_id',
        'stripePaymentIntentId' => 'stripe_payment_intent_id',
        'paymentStatus' => 'payment_status',
        'paymentAmount' => 'payment_amount',
        'paidAt' => 'paid_at',
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
     * Save (insert or update) a pharmacy order.
     *
     * @param array $order Associative array with the order data.
     *
     * @return int Returns the order ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $order): int
    {
        $this->validate($order);

        if (empty($order['id'])) {
            return $this->insert($order);
        } else {
            return $this->update($order);
        }
    }

    /**
     * Validate the order data.
     *
     * @param array $order Associative array with the order data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $order): void
    {
        if (!empty($order['id'])) {
            $count = $this->without_prefix(
                fn() => $this->db->get_where('pharmacy_orders', ['id' => $order['id']])->num_rows(),
            );

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided pharmacy order ID does not exist in the database: ' . $order['id'],
                );
            }
        }

        if (empty($order['id'])) {
            if (empty($order['id_prescriptions'])) {
                throw new InvalidArgumentException('The prescription ID is required.');
            }

            if (empty($order['customer_email'])) {
                throw new InvalidArgumentException('The customer email is required.');
            }

            if (empty($order['fulfillment_method'])) {
                throw new InvalidArgumentException('The fulfillment method is required.');
            }
        }

        if (
            !empty($order['fulfillment_method']) &&
            $order['fulfillment_method'] === 'home_delivery' &&
            empty($order['delivery_address']) &&
            empty($order['id'])
        ) {
            throw new InvalidArgumentException('Delivery address is required for home delivery.');
        }

        if (
            !empty($order['fulfillment_method']) &&
            $order['fulfillment_method'] === 'in_clinic_pickup' &&
            empty($order['id_branches']) &&
            empty($order['id'])
        ) {
            throw new InvalidArgumentException('Branch is required for in-clinic pickup.');
        }
    }

    /**
     * Insert a new pharmacy order into the database.
     *
     * @param array $order Associative array with the order data.
     *
     * @return int Returns the order ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $order): int
    {
        if (empty($order['hash'])) {
            $order['hash'] = bin2hex(random_bytes(32));
        }

        $order['create_datetime'] = date('Y-m-d H:i:s');
        $order['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($order) {
            if (!$this->db->insert('pharmacy_orders', $order)) {
                throw new RuntimeException('Could not insert pharmacy order.');
            }

            return $this->db->insert_id();
        });
    }

    /**
     * Update an existing pharmacy order.
     *
     * @param array $order Associative array with the order data.
     *
     * @return int Returns the order ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $order): int
    {
        $order['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($order) {
            if (!$this->db->update('pharmacy_orders', $order, ['id' => $order['id']])) {
                throw new RuntimeException('Could not update pharmacy order.');
            }

            return $order['id'];
        });
    }

    /**
     * Remove an existing pharmacy order from the database.
     *
     * @param int $order_id Order ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $order_id): void
    {
        $this->without_prefix(fn() => $this->db->delete('pharmacy_orders', ['id' => $order_id]));
    }

    /**
     * Get a specific pharmacy order from the database.
     *
     * @param int $order_id The ID of the record to be returned.
     *
     * @return array Returns an array with the order data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $order_id): array
    {
        $order = $this->without_prefix(
            fn() => $this->db->get_where('pharmacy_orders', ['id' => $order_id])->row_array(),
        );

        if (!$order) {
            throw new InvalidArgumentException(
                'The provided pharmacy order ID was not found in the database: ' . $order_id,
            );
        }

        $this->cast($order);

        return $order;
    }

    /**
     * Find a pharmacy order by its hash.
     *
     * @param string $hash Order hash.
     *
     * @return array Returns an array with the order data.
     *
     * @throws InvalidArgumentException
     */
    public function find_by_hash(string $hash): array
    {
        $order = $this->without_prefix(
            fn() => $this->db->get_where('pharmacy_orders', ['hash' => $hash])->row_array(),
        );

        if (!$order) {
            throw new InvalidArgumentException('The provided pharmacy order hash was not found in the database.');
        }

        $this->cast($order);

        return $order;
    }

    /**
     * Get all pharmacy orders that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of pharmacy orders.
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

            $orders = $this->db->get('pharmacy_orders', $limit, $offset)->result_array();

            foreach ($orders as &$order) {
                $this->cast($order);
            }

            return $orders;
        });
    }

    /**
     * Load related resources to an order.
     *
     * @param array $order Associative array with the order data.
     * @param array $resources Resource names to be attached.
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$order, array $resources): void
    {
        if (empty($order) || empty($resources)) {
            return;
        }

        foreach ($resources as $resource) {
            match ($resource) {
                'items' => $order['items'] = $this->without_prefix(
                    fn() => $this->db
                        ->get_where('pharmacy_order_items', ['id_pharmacy_orders' => $order['id']])
                        ->result_array(),
                ),
                'prescription' => $order['prescription'] = $this->without_prefix(
                    fn() => $this->db
                        ->get_where('prescriptions', ['id' => $order['id_prescriptions']])
                        ->row_array(),
                ),
                default => throw new InvalidArgumentException(
                    'The requested pharmacy order relation is not supported: ' . $resource,
                ),
            };
        }
    }

    /**
     * Convert the database record to the equivalent API resource.
     *
     * @param array $order Order data.
     */
    public function api_encode(array &$order): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $order) ? (int) $order['id'] : null,
            'hash' => $order['hash'] ?? null,
            'prescriptionId' => (int) ($order['id_prescriptions'] ?? 0),
            'customerFirstName' => $order['customer_first_name'] ?? null,
            'customerLastName' => $order['customer_last_name'] ?? null,
            'customerEmail' => $order['customer_email'] ?? null,
            'customerPhone' => $order['customer_phone'] ?? null,
            'fulfillmentMethod' => $order['fulfillment_method'] ?? null,
            'deliveryAddress' => $order['delivery_address'] ?? null,
            'branchId' => $order['id_branches'] ? (int) $order['id_branches'] : null,
            'status' => $order['status'] ?? 'pending',
            'subtotal' => (float) ($order['subtotal'] ?? 0),
            'total' => (float) ($order['total'] ?? 0),
            'currency' => $order['currency'] ?? 'AED',
            'stripeSessionId' => $order['stripe_session_id'] ?? null,
            'stripePaymentIntentId' => $order['stripe_payment_intent_id'] ?? null,
            'paymentStatus' => $order['payment_status'] ?? 'pending',
            'paymentAmount' => $order['payment_amount'] ? (float) $order['payment_amount'] : null,
            'paidAt' => $order['paid_at'] ?? null,
        ];

        if (isset($order['items'])) {
            $encoded_resource['items'] = $order['items'];
        }

        if (isset($order['prescription'])) {
            $encoded_resource['prescription'] = $order['prescription'];
        }

        $order = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database record.
     *
     * @param array $order API resource.
     * @param array|null $base Base data to be overwritten.
     */
    public function api_decode(array &$order, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $order)) {
            $decoded_resource['id'] = $order['id'];
        }

        if (array_key_exists('prescriptionId', $order)) {
            $decoded_resource['id_prescriptions'] = $order['prescriptionId'];
        }

        if (array_key_exists('customerFirstName', $order)) {
            $decoded_resource['customer_first_name'] = $order['customerFirstName'];
        }

        if (array_key_exists('customerLastName', $order)) {
            $decoded_resource['customer_last_name'] = $order['customerLastName'];
        }

        if (array_key_exists('customerEmail', $order)) {
            $decoded_resource['customer_email'] = $order['customerEmail'];
        }

        if (array_key_exists('customerPhone', $order)) {
            $decoded_resource['customer_phone'] = $order['customerPhone'];
        }

        if (array_key_exists('fulfillmentMethod', $order)) {
            $decoded_resource['fulfillment_method'] = $order['fulfillmentMethod'];
        }

        if (array_key_exists('deliveryAddress', $order)) {
            $decoded_resource['delivery_address'] = $order['deliveryAddress'];
        }

        if (array_key_exists('branchId', $order)) {
            $decoded_resource['id_branches'] = $order['branchId'];
        }

        if (array_key_exists('status', $order)) {
            $decoded_resource['status'] = $order['status'];
        }

        $order = $decoded_resource;
    }
}
