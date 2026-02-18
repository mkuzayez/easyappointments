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
 * Stripe payment library.
 *
 * Handles Stripe checkout session creation and payment verification.
 *
 * @package Libraries
 */
class Stripe_payment
{
    /**
     * @var EA_Controller|CI_Controller
     */
    protected EA_Controller|CI_Controller $CI;

    /**
     * @var string
     */
    private string $stripe_secret_key;

    /**
     * Stripe_payment constructor.
     */
    public function __construct()
    {
        $this->CI = &get_instance();

        $this->stripe_secret_key = config('stripe_secret_key');

        if (empty($this->stripe_secret_key)) {
            throw new RuntimeException('Stripe secret key not configured.');
        }

        \Stripe\Stripe::setApiKey($this->stripe_secret_key);
    }

    /**
     * Create a checkout session for appointment payment.
     *
     * @param array $data Session data (amount, currency, customer_email, metadata, success_url, cancel_url).
     *
     * @return array Returns session_id, checkout_url, and expires_at.
     *
     * @throws RuntimeException
     */
    public function create_checkout_session(array $data): array
    {
        try {
            $amount = $data['amount'];
            $currency = $data['currency'] ?? 'AED';
            $customer_email = $data['customer_email'];
            $metadata = $data['metadata'] ?? [];
            $success_url = $data['success_url'] ?? config('stripe_success_url');
            $cancel_url = $data['cancel_url'] ?? config('stripe_cancel_url');

            $amount_cents = (int) round($amount * 100);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($currency),
                            'product_data' => [
                                'name' => 'Medical Consultation',
                                'description' =>
                                    'Expert Medical Center - ' . ($metadata['service_name'] ?? 'Consultation'),
                            ],
                            'unit_amount' => $amount_cents,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
                'customer_email' => $customer_email,
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
            ]);

            return [
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'expires_at' => $session->expires_at,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a checkout session.
     *
     * @param string $session_id Stripe session ID.
     *
     * @return \Stripe\Checkout\Session
     *
     * @throws RuntimeException
     */
    public function get_session(string $session_id): \Stripe\Checkout\Session
    {
        try {
            return \Stripe\Checkout\Session::retrieve($session_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a payment intent.
     *
     * @param string $intent_id Stripe payment intent ID.
     *
     * @return \Stripe\PaymentIntent
     *
     * @throws RuntimeException
     */
    public function get_payment_intent(string $intent_id): \Stripe\PaymentIntent
    {
        try {
            return \Stripe\PaymentIntent::retrieve($intent_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Stripe error: ' . $e->getMessage());
        }
    }

    /**
     * Create a refund for a payment intent.
     *
     * @param string $payment_intent_id Stripe payment intent ID.
     * @param float|null $amount Refund amount (null for full refund). In the base currency unit (e.g. AED, not fils).
     *
     * @return array Returns refund_id, status, amount, and currency.
     *
     * @throws RuntimeException
     */
    public function create_refund(string $payment_intent_id, ?float $amount = null): array
    {
        try {
            $params = [
                'payment_intent' => $payment_intent_id,
            ];

            if ($amount !== null) {
                $params['amount'] = (int) round($amount * 100);
            }

            $refund = \Stripe\Refund::create($params);

            return [
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount / 100,
                'currency' => strtoupper($refund->currency),
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new RuntimeException('Stripe refund error: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment status for a session.
     *
     * @param string $session_id Stripe session ID.
     *
     * @return bool Returns TRUE if the payment is complete.
     */
    public function verify_payment(string $session_id): bool
    {
        try {
            $session = $this->get_session($session_id);

            return $session->payment_status === 'paid';
        } catch (Exception $e) {
            return false;
        }
    }
}
