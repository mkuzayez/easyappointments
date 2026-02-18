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
 * Patient feedback model.
 *
 * Handles all the database operations of the patient feedback resource.
 * Uses raw table name 'patient_feedback' (no ea_ prefix).
 *
 * @package Models
 */
class Patient_feedback_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'id_appointments' => 'integer',
        'id_users_customer' => 'integer',
        'id_users_provider' => 'integer',
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'appointmentId' => 'id_appointments',
        'customerId' => 'id_users_customer',
        'providerId' => 'id_users_provider',
        'rating' => 'rating',
        'feedbackText' => 'feedback_text',
        'feedbackCategory' => 'feedback_category',
        'isApproved' => 'is_approved',
        'submittedDate' => 'submitted_date',
        'approvedDate' => 'approved_date',
    ];

    /**
     * Run a callback with the db prefix temporarily removed (for the unprefixed patient_feedback table).
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
     * Save (insert or update) a feedback record.
     *
     * @param array $feedback Associative array with the feedback data.
     *
     * @return int Returns the feedback ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $feedback): int
    {
        $this->validate($feedback);

        if (empty($feedback['id'])) {
            return $this->insert($feedback);
        } else {
            return $this->update($feedback);
        }
    }

    /**
     * Validate the feedback data.
     *
     * @param array $feedback Associative array with the feedback data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $feedback): void
    {
        if (!empty($feedback['id'])) {
            $count = $this->without_prefix(
                fn() => $this->db->get_where('patient_feedback', ['id' => $feedback['id']])->num_rows(),
            );

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided feedback ID does not exist in the database: ' . $feedback['id'],
                );
            }
        }

        if (empty($feedback['id'])) {
            if (empty($feedback['id_appointments'])) {
                throw new InvalidArgumentException('The appointment ID is required.');
            }

            if (empty($feedback['rating'])) {
                throw new InvalidArgumentException('The rating is required.');
            }
        }

        if (isset($feedback['rating']) && ($feedback['rating'] < 1 || $feedback['rating'] > 5)) {
            throw new InvalidArgumentException('The rating must be between 1 and 5.');
        }

        if (!empty($feedback['id_appointments'])) {
            $appointment_count = $this->db
                ->get_where('appointments', ['id' => $feedback['id_appointments']])
                ->num_rows();

            if (!$appointment_count) {
                throw new InvalidArgumentException(
                    'The provided appointment ID does not exist: ' . $feedback['id_appointments'],
                );
            }

            if (empty($feedback['id']) && $this->exists_for_appointment($feedback['id_appointments'])) {
                throw new InvalidArgumentException(
                    'Feedback already exists for this appointment: ' . $feedback['id_appointments'],
                );
            }
        }
    }

    /**
     * Insert a new feedback record into the database.
     *
     * @param array $feedback Associative array with the feedback data.
     *
     * @return int Returns the feedback ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $feedback): int
    {
        $feedback['create_datetime'] = date('Y-m-d H:i:s');
        $feedback['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($feedback) {
            if (!$this->db->insert('patient_feedback', $feedback)) {
                throw new RuntimeException('Could not insert feedback.');
            }

            return $this->db->insert_id();
        });
    }

    /**
     * Update an existing feedback record.
     *
     * @param array $feedback Associative array with the feedback data.
     *
     * @return int Returns the feedback ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $feedback): int
    {
        $feedback['update_datetime'] = date('Y-m-d H:i:s');

        return $this->without_prefix(function () use ($feedback) {
            if (!$this->db->update('patient_feedback', $feedback, ['id' => $feedback['id']])) {
                throw new RuntimeException('Could not update feedback.');
            }

            return $feedback['id'];
        });
    }

    /**
     * Remove an existing feedback record from the database.
     *
     * @param int $feedback_id Feedback ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $feedback_id): void
    {
        $this->without_prefix(fn() => $this->db->delete('patient_feedback', ['id' => $feedback_id]));
    }

    /**
     * Get a specific feedback record from the database.
     *
     * @param int $feedback_id The ID of the record to be returned.
     *
     * @return array Returns an array with the feedback data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $feedback_id): array
    {
        $feedback = $this->without_prefix(
            fn() => $this->db->get_where('patient_feedback', ['id' => $feedback_id])->row_array(),
        );

        if (!$feedback) {
            throw new InvalidArgumentException(
                'The provided feedback ID was not found in the database: ' . $feedback_id,
            );
        }

        $this->cast($feedback);

        return $feedback;
    }

    /**
     * Get all feedback records that match the provided criteria.
     *
     * @param array|string|null $where Where conditions.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of feedback records.
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

            $records = $this->db->get('patient_feedback', $limit, $offset)->result_array();

            foreach ($records as &$record) {
                $this->cast($record);
            }

            return $records;
        });
    }

    /**
     * Approve a feedback record.
     *
     * @param int $id Feedback ID.
     *
     * @throws InvalidArgumentException
     */
    public function approve(int $id): void
    {
        $this->find($id);

        $this->without_prefix(
            fn() => $this->db->update(
                'patient_feedback',
                [
                    'is_approved' => 1,
                    'approved_date' => date('Y-m-d H:i:s'),
                    'update_datetime' => date('Y-m-d H:i:s'),
                ],
                ['id' => $id],
            ),
        );
    }

    /**
     * Get the average rating for a provider.
     *
     * @param int $provider_id Provider user ID.
     *
     * @return float Average rating (0.0 if no approved feedback).
     */
    public function get_average_rating(int $provider_id): float
    {
        return $this->without_prefix(function () use ($provider_id) {
            $result = $this->db
                ->select_avg('rating')
                ->from('patient_feedback')
                ->where('id_users_provider', $provider_id)
                ->where('is_approved', 1)
                ->get()
                ->row();

            return $result && $result->rating ? round((float) $result->rating, 1) : 0.0;
        });
    }

    /**
     * Check if feedback already exists for an appointment.
     *
     * @param int $appointment_id Appointment ID.
     *
     * @return bool
     */
    public function exists_for_appointment(int $appointment_id): bool
    {
        return $this->without_prefix(
            fn() => $this->db->get_where('patient_feedback', ['id_appointments' => $appointment_id])->num_rows() > 0,
        );
    }

    /**
     * Get all approved feedback for a provider.
     *
     * @param int $provider_id Provider user ID.
     *
     * @return array
     */
    public function get_approved_by_provider(int $provider_id): array
    {
        return $this->without_prefix(function () use ($provider_id) {
            $records = $this->db
                ->get_where('patient_feedback', [
                    'id_users_provider' => $provider_id,
                    'is_approved' => 1,
                ])
                ->result_array();

            foreach ($records as &$record) {
                $this->cast($record);
            }

            return $records;
        });
    }

    /**
     * Convert the database feedback record to the equivalent API resource.
     *
     * @param array $feedback Feedback data.
     */
    public function api_encode(array &$feedback): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $feedback) ? (int) $feedback['id'] : null,
            'appointmentId' => (int) $feedback['id_appointments'],
            'customerId' => (int) $feedback['id_users_customer'],
            'providerId' => (int) $feedback['id_users_provider'],
            'rating' => (int) $feedback['rating'],
            'feedbackText' => $feedback['feedback_text'] ?? null,
            'feedbackCategory' => $feedback['feedback_category'],
            'isApproved' => (bool) $feedback['is_approved'],
            'submittedDate' => $feedback['submitted_date'] ?? null,
            'approvedDate' => $feedback['approved_date'] ?? null,
        ];

        $feedback = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database feedback record.
     *
     * @param array $feedback API resource.
     * @param array|null $base Base feedback data to be overwritten with the provided values.
     */
    public function api_decode(array &$feedback, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $feedback)) {
            $decoded_resource['id'] = $feedback['id'];
        }

        if (array_key_exists('appointmentId', $feedback)) {
            $decoded_resource['id_appointments'] = $feedback['appointmentId'];
        }

        if (array_key_exists('customerId', $feedback)) {
            $decoded_resource['id_users_customer'] = $feedback['customerId'];
        }

        if (array_key_exists('providerId', $feedback)) {
            $decoded_resource['id_users_provider'] = $feedback['providerId'];
        }

        if (array_key_exists('rating', $feedback)) {
            $decoded_resource['rating'] = (int) $feedback['rating'];
        }

        if (array_key_exists('feedbackText', $feedback)) {
            $decoded_resource['feedback_text'] = $feedback['feedbackText'];
        }

        if (array_key_exists('feedbackCategory', $feedback)) {
            $decoded_resource['feedback_category'] = $feedback['feedbackCategory'];
        }

        if (array_key_exists('isApproved', $feedback)) {
            $decoded_resource['is_approved'] = (bool) $feedback['isApproved'] ? 1 : 0;
        }

        $feedback = $decoded_resource;
    }
}
