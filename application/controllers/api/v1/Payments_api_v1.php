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
 * Payments API v1 controller.
 *
 * @package Controllers
 */
class Payments_api_v1 extends EA_Controller
{
    /**
     * Payments_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');
        $this->load->library('stripe_payment');

        $this->api->auth();
    }

    /**
     * Create a Stripe checkout session for payment.
     *
     * POST /api/v1/payments/create-session
     */
    public function create_session(): void
    {
        try {
            $request = request();

            if (empty($request['serviceId']) || empty($request['customerEmail'])) {
                throw new InvalidArgumentException('serviceId and customerEmail are required.');
            }

            // Always source the price from the service record — never trust client-supplied amount.
            $this->load->model('services_model');
            $service = $this->services_model->find((int) $request['serviceId']);

            $amount = (float) $service['price'];

            if ($amount <= 0) {
                throw new InvalidArgumentException('Service does not have a valid price configured.');
            }

            $metadata = $request['metadata'] ?? [];
            $metadata['service_id'] = (string) $service['id'];

            $data = [
                'amount' => $amount,
                'currency' => $service['currency'] ?? 'AED',
                'customer_email' => $request['customerEmail'],
                'metadata' => $metadata,
                'success_url' => $request['successUrl'] ?? config('stripe_success_url'),
                'cancel_url' => $request['cancelUrl'] ?? config('stripe_cancel_url'),
            ];

            $result = $this->stripe_payment->create_checkout_session($data);

            json_response([
                'sessionId' => $result['session_id'],
                'checkoutUrl' => $result['checkout_url'],
                'expiresAt' => date('c', $result['expires_at']),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Verify payment status.
     *
     * POST /api/v1/payments/verify
     */
    public function verify(): void
    {
        try {
            $request = request();

            if (empty($request['sessionId'])) {
                throw new InvalidArgumentException('Session ID is required.');
            }

            $session_id = $request['sessionId'];
            $is_paid = $this->stripe_payment->verify_payment($session_id);

            if ($is_paid) {
                $session = $this->stripe_payment->get_session($session_id);

                json_response([
                    'status' => 'paid',
                    'paymentIntentId' => $session->payment_intent,
                    'amountTotal' => $session->amount_total / 100,
                    'currency' => strtoupper($session->currency),
                ]);
            } else {
                json_response([
                    'status' => 'pending',
                ]);
            }
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Process a refund for a paid appointment.
     *
     * POST /api/v1/payments/{id}/refund
     *
     * @param int $id Payment/appointment identifier (unused — appointmentId comes from body).
     */
    public function refund(int $id): void
    {
        try {
            $request = request();

            if (empty($request['appointmentId'])) {
                throw new InvalidArgumentException('appointmentId is required.');
            }

            $this->load->model('appointments_model');

            $appointment = $this->appointments_model->find((int) $request['appointmentId']);

            if (empty($appointment['payment_status']) || $appointment['payment_status'] !== 'paid') {
                throw new InvalidArgumentException('Only paid appointments can be refunded.');
            }

            if (empty($appointment['stripe_payment_intent_id'])) {
                throw new InvalidArgumentException('No Stripe payment intent found for this appointment.');
            }

            $refund_amount = isset($request['amount']) ? (float) $request['amount'] : null;

            $refund = $this->stripe_payment->create_refund(
                $appointment['stripe_payment_intent_id'],
                $refund_amount,
            );

            $this->appointments_model->save([
                'id' => $appointment['id'],
                'start_datetime' => $appointment['start_datetime'],
                'end_datetime' => $appointment['end_datetime'],
                'id_services' => $appointment['id_services'],
                'id_users_provider' => $appointment['id_users_provider'],
                'id_users_customer' => $appointment['id_users_customer'],
                'payment_status' => 'refunded',
                'refund_amount' => $refund['amount'],
                'refund_status' => $refund['status'] === 'succeeded' ? 'completed' : $refund['status'],
                'refund_reason' => $request['reason'] ?? null,
            ]);

            json_response([
                'refundId' => $refund['refund_id'],
                'status' => $refund['status'],
                'amount' => $refund['amount'],
                'currency' => $refund['currency'],
                'appointmentId' => $appointment['id'],
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
