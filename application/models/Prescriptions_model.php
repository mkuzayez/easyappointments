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
 * Prescriptions model.
 *
 * Handles all the database operations of the prescription resource.
 * Uses raw table name 'prescriptions' (no ea_ prefix).
 *
 * @package Models
 */
class Prescriptions_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'id_appointments' => 'integer',
        'id_users_provider' => 'integer',
        'id_users_customer' => 'integer',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'hash' => 'hash',
        'appointmentId' => 'id_appointments',
        'providerId' => 'id_users_provider',
        'customerId' => 'id_users_customer',
        'notes' => 'notes',
        'prescribedDate' => 'prescribed_date',
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
     * Save (insert or update) a prescription.
     *
     * @param array $prescription Associative array with the prescription data.
     *
     * @return int Returns the prescription ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $prescription): int
    {
        $this->validate($prescription);

        if (empty($prescription['id'])) {
            return $this->insert($prescription);
        } else {
            return $this->update($prescription);
        }
    }

    /**
     * Validate the prescription data.
     *
     * @param array $prescription Associative array with the prescription data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $prescription): void
    {
        if (!empty($prescription['id'])) {
            $count = $this->without_prefix(
                fn() => $this->db->get_where('prescriptions', ['id' => $prescription['id']])->num_rows(),
            );

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided prescription ID does not exist in the database: ' . $prescription['id'],
                );
            }
        }

        if (empty($prescription['id'])) {
            if (empty($prescription['id_appointments'])) {
                throw new InvalidArgumentException('The appointment ID is required.');
            }

            if (empty($prescription['id_users_provider'])) {
                throw new InvalidArgumentException('The provider ID is required.');
            }

            if (empty($prescription['id_users_customer'])) {
                throw new InvalidArgumentException('The customer ID is required.');
            }
        }
    }

    /**
     * Insert a new prescription into the database.
     *
     * @param array $prescription Associative array with the prescription data.
     *
     * @return int Returns the prescription ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $prescription): int
    {
        if (empty($prescription['hash'])) {
            $prescription['hash'] = bin2hex(random_bytes(32));
        }

        if (empty($prescription['prescribed_date'])) {
            $prescription['prescribed_date'] = date('Y-m-d H:i:s');
        }

        $prescription['create_datetime'] = date('Y-m-d H:i:s');
        $prescription['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($prescription) {
            if (!$this->db->insert('prescriptions', $prescription)) {
                throw new RuntimeException('Could not insert prescription.');
            }

            return $this->db->insert_id();
        });
    }

    /**
     * Update an existing prescription.
     *
     * @param array $prescription Associative array with the prescription data.
     *
     * @return int Returns the prescription ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $prescription): int
    {
        $prescription['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($prescription) {
            if (!$this->db->update('prescriptions', $prescription, ['id' => $prescription['id']])) {
                throw new RuntimeException('Could not update prescription.');
            }

            return $prescription['id'];
        });
    }

    /**
     * Remove an existing prescription from the database.
     *
     * @param int $prescription_id Prescription ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $prescription_id): void
    {
        $this->without_prefix(fn() => $this->db->delete('prescriptions', ['id' => $prescription_id]));
    }

    /**
     * Get a specific prescription from the database.
     *
     * @param int $prescription_id The ID of the record to be returned.
     *
     * @return array Returns an array with the prescription data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $prescription_id): array
    {
        $prescription = $this->without_prefix(
            fn() => $this->db->get_where('prescriptions', ['id' => $prescription_id])->row_array(),
        );

        if (!$prescription) {
            throw new InvalidArgumentException(
                'The provided prescription ID was not found in the database: ' . $prescription_id,
            );
        }

        $this->cast($prescription);

        return $prescription;
    }

    /**
     * Find a prescription by its hash.
     *
     * @param string $hash Prescription hash.
     *
     * @return array Returns an array with the prescription data.
     *
     * @throws InvalidArgumentException
     */
    public function find_by_hash(string $hash): array
    {
        $prescription = $this->without_prefix(
            fn() => $this->db->get_where('prescriptions', ['hash' => $hash])->row_array(),
        );

        if (!$prescription) {
            throw new InvalidArgumentException('The provided prescription hash was not found in the database.');
        }

        $this->cast($prescription);

        return $prescription;
    }

    /**
     * Get all prescriptions that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of prescriptions.
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

            $prescriptions = $this->db->get('prescriptions', $limit, $offset)->result_array();

            foreach ($prescriptions as &$prescription) {
                $this->cast($prescription);
            }

            return $prescriptions;
        });
    }

    /**
     * Load related resources to a prescription.
     *
     * @param array $prescription Associative array with the prescription data.
     * @param array $resources Resource names to be attached.
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$prescription, array $resources): void
    {
        if (empty($prescription) || empty($resources)) {
            return;
        }

        foreach ($resources as $resource) {
            match ($resource) {
                'items' => $prescription['items'] = $this->without_prefix(
                    fn() => $this->db
                        ->get_where('prescription_items', ['id_prescriptions' => $prescription['id']])
                        ->result_array(),
                ),
                'appointment' => $prescription['appointment'] = $this->db
                    ->get_where('appointments', ['id' => $prescription['id_appointments']])
                    ->row_array(),
                default => throw new InvalidArgumentException(
                    'The requested prescription relation is not supported: ' . $resource,
                ),
            };
        }
    }

    /**
     * Convert the database record to the equivalent API resource.
     *
     * @param array $prescription Prescription data.
     */
    public function api_encode(array &$prescription): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $prescription) ? (int) $prescription['id'] : null,
            'hash' => $prescription['hash'] ?? null,
            'appointmentId' => (int) ($prescription['id_appointments'] ?? 0),
            'providerId' => (int) ($prescription['id_users_provider'] ?? 0),
            'customerId' => (int) ($prescription['id_users_customer'] ?? 0),
            'notes' => $prescription['notes'] ?? null,
            'prescribedDate' => $prescription['prescribed_date'] ?? null,
        ];

        if (isset($prescription['items'])) {
            $encoded_resource['items'] = $prescription['items'];
        }

        if (isset($prescription['appointment'])) {
            $encoded_resource['appointment'] = $prescription['appointment'];
        }

        $prescription = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database record.
     *
     * @param array $prescription API resource.
     * @param array|null $base Base data to be overwritten.
     */
    public function api_decode(array &$prescription, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $prescription)) {
            $decoded_resource['id'] = $prescription['id'];
        }

        if (array_key_exists('appointmentId', $prescription)) {
            $decoded_resource['id_appointments'] = $prescription['appointmentId'];
        }

        if (array_key_exists('providerId', $prescription)) {
            $decoded_resource['id_users_provider'] = $prescription['providerId'];
        }

        if (array_key_exists('customerId', $prescription)) {
            $decoded_resource['id_users_customer'] = $prescription['customerId'];
        }

        if (array_key_exists('notes', $prescription)) {
            $decoded_resource['notes'] = $prescription['notes'];
        }

        if (array_key_exists('prescribedDate', $prescription)) {
            $decoded_resource['prescribed_date'] = $prescription['prescribedDate'];
        }

        $prescription = $decoded_resource;
    }
}
