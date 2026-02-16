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

            if (empty($request['amount']) || empty($request['customerEmail'])) {
                throw new InvalidArgumentException('Amount and customerEmail are required.');
            }

            $data = [
                'amount' => (float) $request['amount'],
                'currency' => $request['currency'] ?? 'AED',
                'customer_email' => $request['customerEmail'],
                'metadata' => $request['metadata'] ?? [],
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
}
