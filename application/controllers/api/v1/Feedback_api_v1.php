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
 * Feedback API v1 controller.
 *
 * @package Controllers
 */
class Feedback_api_v1 extends EA_Controller
{
    /**
     * Feedback_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('patient_feedback_model');
        $this->load->library('api');

        $this->api->auth();

        $this->api->model('patient_feedback_model');
    }

    /**
     * Get a feedback collection.
     */
    public function index(): void
    {
        try {
            $limit = $this->api->request_limit();

            $offset = $this->api->request_offset();

            $order_by = $this->api->request_order_by();

            $fields = $this->api->request_fields();

            $where = [];

            $provider_id = request('providerId');

            if ($provider_id !== null) {
                $where['id_users_provider'] = (int) $provider_id;
            }

            $customer_id = request('customerId');

            if ($customer_id !== null) {
                $where['id_users_customer'] = (int) $customer_id;
            }

            $appointment_id = request('appointmentId');

            if ($appointment_id !== null) {
                $where['id_appointments'] = (int) $appointment_id;
            }

            $approved = request('approved');

            if ($approved !== null) {
                $where['is_approved'] = filter_var($approved, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            $feedback_records = $this->patient_feedback_model->get(
                !empty($where) ? $where : null,
                $limit,
                $offset,
                $order_by,
            );

            foreach ($feedback_records as &$feedback) {
                $this->patient_feedback_model->api_encode($feedback);

                if (!empty($fields)) {
                    $this->patient_feedback_model->only($feedback, $fields);
                }
            }

            json_response($feedback_records);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single feedback record.
     *
     * @param int|null $id Feedback ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->patient_feedback_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $feedback = $this->patient_feedback_model->find($id);

            $this->patient_feedback_model->api_encode($feedback);

            if (!empty($fields)) {
                $this->patient_feedback_model->only($feedback, $fields);
            }

            json_response($feedback);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new feedback record.
     */
    public function store(): void
    {
        try {
            $feedback = request();

            $this->patient_feedback_model->api_decode($feedback);

            if (array_key_exists('id', $feedback)) {
                unset($feedback['id']);
            }

            $feedback['submitted_date'] = date('Y-m-d H:i:s');

            $feedback_id = $this->patient_feedback_model->save($feedback);

            $created_feedback = $this->patient_feedback_model->find($feedback_id);

            $this->patient_feedback_model->api_encode($created_feedback);

            json_response($created_feedback, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a feedback record.
     *
     * @param int $id Feedback ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->patient_feedback_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_feedback = $occurrences[0];

            $feedback = request();

            $this->patient_feedback_model->api_decode($feedback, $original_feedback);

            $feedback_id = $this->patient_feedback_model->save($feedback);

            $updated_feedback = $this->patient_feedback_model->find($feedback_id);

            $this->patient_feedback_model->api_encode($updated_feedback);

            json_response($updated_feedback);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete a feedback record.
     *
     * @param int $id Feedback ID.
     */
    public function destroy(int $id): void
    {
        try {
            $occurrences = $this->patient_feedback_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $this->patient_feedback_model->delete($id);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Approve a feedback record.
     *
     * @param int $id Feedback ID.
     */
    public function approve(int $id): void
    {
        try {
            $occurrences = $this->patient_feedback_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $this->patient_feedback_model->approve($id);

            $feedback = $this->patient_feedback_model->find($id);

            $this->patient_feedback_model->api_encode($feedback);

            json_response($feedback);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get the average rating for a provider.
     *
     * @param int $id Provider user ID.
     */
    public function provider_rating(int $id): void
    {
        try {
            $average_rating = $this->patient_feedback_model->get_average_rating($id);

            $approved_feedback = $this->patient_feedback_model->get_approved_by_provider($id);

            json_response([
                'providerId' => $id,
                'averageRating' => $average_rating,
                'totalReviews' => count($approved_feedback),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
