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
 * Appointments API v1 controller.
 *
 * @package Controllers
 */
class Appointments_api_v1 extends EA_Controller
{
    /**
     * Appointments_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->model('services_model');
        $this->load->model('settings_model');

        $this->load->library('api');
        $this->load->library('webhooks_client');
        $this->load->library('synchronization');
        $this->load->library('notifications');

        $this->api->auth();

        $this->api->model('appointments_model');
    }

    /**
     * Get an appointment collection.
     */
    public function index(): void
    {
        try {
            $keyword = $this->api->request_keyword();

            $limit = $this->api->request_limit();

            $offset = $this->api->request_offset();

            $order_by = $this->api->request_order_by();

            $fields = $this->api->request_fields();

            $with = $this->api->request_with();

            $where = null;

            // Date query param.

            $date = request('date');

            if (!empty($date)) {
                $where['DATE(start_datetime)'] = (new DateTime($date))->format('Y-m-d');
            }

            // From query param.

            $from = request('from');

            if (!empty($from)) {
                $where['DATE(start_datetime) >='] = (new DateTime($from))->format('Y-m-d');
            }

            // Till query param.

            $till = request('till');

            if (!empty($till)) {
                $where['DATE(end_datetime) <='] = (new DateTime($till))->format('Y-m-d');
            }

            // Service ID query param.

            $service_id = request('serviceId');

            if (!empty($service_id)) {
                $where['id_services'] = $service_id;
            }

            // Provider ID query param.

            $provider_id = request('providerId');

            if (!empty($provider_id)) {
                $where['id_users_provider'] = $provider_id;
            }

            // Customer ID query param.

            $customer_id = request('customerId');

            if (!empty($customer_id)) {
                $where['id_users_customer'] = $customer_id;
            }

            $appointments = empty($keyword)
                ? $this->appointments_model->get($where, $limit, $offset, $order_by)
                : $this->appointments_model->search($keyword, $limit, $offset, $order_by);

            foreach ($appointments as &$appointment) {
                $this->appointments_model->api_encode($appointment);

                $this->aggregates($appointment);

                if (!empty($fields)) {
                    $this->appointments_model->only($appointment, $fields);
                }

                if (!empty($with)) {
                    $this->appointments_model->load($appointment, $with);
                }
            }

            json_response($appointments);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Load the relations of the current appointment if the "aggregates" query parameter is present.
     *
     * This is a compatibility addition to the appointment resource which was the only one to support it.
     *
     * Use the "attach" query parameter instead as this one will be removed.
     *
     * @param array $appointment Appointment data.
     *
     * @deprecated Since 1.5
     */
    private function aggregates(array &$appointment): void
    {
        $aggregates = request('aggregates') !== null;

        if ($aggregates) {
            $appointment['service'] = $this->services_model->find(
                $appointment['id_services'] ?? ($appointment['serviceId'] ?? null),
            );
            $appointment['provider'] = $this->providers_model->find(
                $appointment['id_users_provider'] ?? ($appointment['providerId'] ?? null),
            );
            $appointment['customer'] = $this->customers_model->find(
                $appointment['id_users_customer'] ?? ($appointment['customerId'] ?? null),
            );
            $this->services_model->api_encode($appointment['service']);
            $this->providers_model->api_encode($appointment['provider']);
            $this->customers_model->api_encode($appointment['customer']);
        }
    }

    /**
     * Get a single appointment.
     *
     * @param int|null $id Appointment ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->appointments_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $with = $this->api->request_with();

            $appointment = $this->appointments_model->find($id);

            $this->appointments_model->api_encode($appointment);

            if (!empty($fields)) {
                $this->appointments_model->only($appointment, $fields);
            }

            if (!empty($with)) {
                $this->appointments_model->load($appointment, $with);
            }

            json_response($appointment);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new appointment.
     */
    public function store(): void
    {
        try {
            $appointment = request();

            $this->appointments_model->api_decode($appointment);

            if (array_key_exists('id', $appointment)) {
                unset($appointment['id']);
            }

            if (!array_key_exists('end_datetime', $appointment)) {
                $appointment['end_datetime'] = $this->appointments_model->calculate_end_datetime($appointment);
            }

            // Handle payment verification if online payment.
            if (!empty($appointment['stripe_session_id'])) {
                $this->load->library('stripe_payment');

                // Prevent replay: reject if this session ID is already used by another appointment.
                $existing = $this->db
                    ->where('stripe_session_id', $appointment['stripe_session_id'])
                    ->get('appointments')
                    ->row_array();

                if ($existing) {
                    throw new InvalidArgumentException('This Stripe session has already been used for appointment #' . $existing['id'] . '.');
                }

                $is_paid = $this->stripe_payment->verify_payment($appointment['stripe_session_id']);

                if (!$is_paid) {
                    throw new RuntimeException('Payment not verified. Please complete payment first.');
                }

                $session = $this->stripe_payment->get_session($appointment['stripe_session_id']);

                // Verify the session was created for the same service being booked.
                $session_service_id = $session->metadata['service_id'] ?? null;

                if ($session_service_id === null) {
                    throw new InvalidArgumentException('Stripe session is missing service_id metadata. Payment cannot be verified against the booked service.');
                }

                if ((int) $session_service_id !== (int) $appointment['id_services']) {
                    throw new InvalidArgumentException('Payment session was created for a different service (service #' . $session_service_id . '). Cannot book service #' . $appointment['id_services'] . ' with this payment.');
                }

                // Verify the paid amount matches the service price.
                $service = $this->services_model->find((int) $appointment['id_services']);
                $expected_amount_cents = (int) round((float) $service['price'] * 100);

                if ($session->amount_total !== $expected_amount_cents) {
                    throw new InvalidArgumentException('Payment amount does not match the service price.');
                }

                // Prevent replay via payment intent as well.
                $existing_intent = $this->db
                    ->where('stripe_payment_intent_id', $session->payment_intent)
                    ->get('appointments')
                    ->row_array();

                if ($existing_intent) {
                    throw new InvalidArgumentException('This payment intent has already been used for appointment #' . $existing_intent['id'] . '.');
                }

                $appointment['payment_status'] = 'paid';
                $appointment['payment_method'] = 'online';
                $appointment['stripe_payment_intent_id'] = $session->payment_intent;
                $appointment['payment_amount'] = $session->amount_total / 100;
                $appointment['payment_currency'] = strtoupper($session->currency);
            } elseif (
                !empty($appointment['payment_method']) &&
                $appointment['payment_method'] === 'offline'
            ) {
                $appointment['payment_status'] = 'pending';
            } else {
                // No valid payment path — reject the appointment.
                throw new InvalidArgumentException(
                    'Payment is required. Provide a valid stripeSessionId for online payment or set paymentMethod to "offline".'
                );
            }

            $appointment_id = $this->appointments_model->save($appointment);

            $created_appointment = $this->appointments_model->find($appointment_id);

            $this->notify_and_sync_appointment($created_appointment);

            $this->appointments_model->api_encode($created_appointment);

            json_response($created_appointment, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Send the required notifications and trigger syncing after saving an appointment.
     *
     * @param array $appointment Appointment data.
     * @param string $action Performed action ("store" or "update").
     * @param array|null $original_appointment Original appointment data before update (for detecting changes).
     */
    private function notify_and_sync_appointment(
        array $appointment,
        string $action = 'store',
        ?array $original_appointment = null,
    ): void {
        $manage_mode = $action === 'update';

        $service = $this->services_model->find($appointment['id_services']);

        $provider = $this->providers_model->find($appointment['id_users_provider']);

        $customer = $this->customers_model->find($appointment['id_users_customer']);

        $company_color = setting('company_color');

        $settings = [
            'company_name' => setting('company_name'),
            'company_email' => setting('company_email'),
            'company_link' => setting('company_link'),
            'company_color' =>
                !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
            'date_format' => setting('date_format'),
            'time_format' => setting('time_format'),
        ];

        $this->synchronization->sync_appointment_saved($appointment, $service, $provider, $customer, $settings);

        // Skip customer email in legacy notifier — multilingual notifications handle customer emails.
        $this->notifications->notify_appointment_saved(
            $appointment,
            $service,
            $provider,
            $customer,
            $settings,
            $manage_mode,
            skip_customer: true,
        );

        // Send branded multilingual emails to the customer based on the action and appointment state.
        try {
            $this->load->library('multilingual_notifications');

            if ($action === 'store') {
                if (
                    !empty($appointment['payment_method']) &&
                    $appointment['payment_method'] === 'offline'
                ) {
                    // Offline booking — send payment pending email.
                    $frontend_base = rtrim(config('frontend_url'), '/');
                    $payment_link = $frontend_base . '/booking/payment/' . ($appointment['hash'] ?? '');

                    $this->multilingual_notifications->send_payment_pending(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                        $payment_link,
                    );
                } else {
                    // Online/paid booking — send confirmation email.
                    $this->multilingual_notifications->send_appointment_confirmation(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                    );
                }
            } elseif ($action === 'update' && $original_appointment) {
                $old_status = $original_appointment['status'] ?? null;
                $new_status = $appointment['status'] ?? null;
                $old_start = $original_appointment['start_datetime'] ?? null;
                $new_start = $appointment['start_datetime'] ?? null;

                if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
                    // Status changed to cancelled — send cancellation email.
                    $this->multilingual_notifications->send_appointment_cancelled(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                    );
                } elseif ($new_start !== $old_start) {
                    // Start datetime changed — send rescheduled email.
                    $this->multilingual_notifications->send_appointment_rescheduled(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                    );
                } elseif ($new_status === 'completed' && $old_status !== 'completed') {
                    // Status changed to completed — send feedback request email.
                    $frontend_base = rtrim(config('frontend_url'), '/');
                    $feedback_link = $frontend_base . '/feedback/' . ($appointment['hash'] ?? '');

                    $this->multilingual_notifications->send_feedback_request(
                        $appointment,
                        $provider,
                        $customer,
                        $feedback_link,
                    );
                } else {
                    // Generic update — send confirmation email.
                    $this->multilingual_notifications->send_appointment_confirmation(
                        $appointment,
                        $service,
                        $provider,
                        $customer,
                    );
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Multilingual email notification failed: ' . $e->getMessage());
        }

        $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_SAVE, $appointment);
    }

    /**
     * Update an appointment.
     *
     * @param int $id Appointment ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->appointments_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_appointment = $occurrences[0];

            $appointment = request();

            $this->appointments_model->api_decode($appointment, $original_appointment);

            $appointment_id = $this->appointments_model->save($appointment);

            $updated_appointment = $this->appointments_model->find($appointment_id);

            $this->notify_and_sync_appointment($updated_appointment, 'update', $original_appointment);

            $this->appointments_model->api_encode($updated_appointment);

            json_response($updated_appointment);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete an appointment.
     *
     * @param int $id Appointment ID.
     */
    public function destroy(int $id): void
    {
        try {
            $occurrences = $this->appointments_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $deleted_appointment = $occurrences[0];

            $service = $this->services_model->find($deleted_appointment['id_services']);

            $provider = $this->providers_model->find($deleted_appointment['id_users_provider']);

            $customer = $this->customers_model->find($deleted_appointment['id_users_customer']);

            $company_color = setting('company_color');

            $settings = [
                'company_name' => setting('company_name'),
                'company_email' => setting('company_email'),
                'company_link' => setting('company_link'),
                'company_color' =>
                    !empty($company_color) && $company_color != DEFAULT_COMPANY_COLOR ? $company_color : null,
                'date_format' => setting('date_format'),
                'time_format' => setting('time_format'),
            ];

            $this->appointments_model->delete($id);

            $this->synchronization->sync_appointment_deleted($deleted_appointment, $provider);

            $this->notifications->notify_appointment_deleted(
                $deleted_appointment,
                $service,
                $provider,
                $customer,
                $settings,
            );

            $this->webhooks_client->trigger(WEBHOOK_APPOINTMENT_DELETE, $deleted_appointment);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
