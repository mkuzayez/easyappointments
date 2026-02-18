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
 * Pharmacy Cart API v1 controller.
 *
 * Public (no auth) endpoints for the patient-facing pharmacy cart.
 *
 * @package Controllers
 */
class Pharmacy_cart_api_v1 extends EA_Controller
{
    /**
     * Pharmacy_cart_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('prescriptions_model');
        $this->load->model('prescription_items_model');
        $this->load->model('pharmacy_orders_model');
        $this->load->model('pharmacy_order_items_model');
        $this->load->model('customers_model');
        $this->load->library('stripe_payment');
        $this->load->library('multilingual_notifications');
    }

    /**
     * Get a prescription by hash for the cart page.
     *
     * GET /api/v1/pharmacy/cart/{hash}
     *
     * @param string $hash Prescription hash.
     */
    public function get_prescription(string $hash): void
    {
        try {
            $prescription = $this->prescriptions_model->find_by_hash($hash);

            $items = $this->prescription_items_model->get_by_prescription($prescription['id']);

            $subtotal = 0;

            foreach ($items as &$item) {
                $line_total = (float) $item['medicine_price'] * (int) $item['quantity'];
                $subtotal += $line_total;
                $this->prescription_items_model->api_encode($item);
                $item['lineTotal'] = $line_total;
            }

            // Load customer info.
            $customer = null;

            try {
                $customer = $this->customers_model->find((int) $prescription['id_users_customer']);
            } catch (Throwable $e) {
                log_message('error', 'Pharmacy cart: Could not load customer: ' . $e->getMessage());
            }

            $this->prescriptions_model->api_encode($prescription);

            $prescription['items'] = $items;
            $prescription['subtotal'] = round($subtotal, 2);
            $prescription['total'] = round($subtotal, 2);
            $prescription['currency'] = 'AED';

            if ($customer) {
                $prescription['customer'] = [
                    'firstName' => $customer['first_name'] ?? '',
                    'lastName' => $customer['last_name'] ?? '',
                    'email' => $customer['email'] ?? '',
                    'phone' => $customer['phone_number'] ?? '',
                ];
            }

            json_response($prescription);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create a pharmacy order and Stripe checkout session.
     *
     * POST /api/v1/pharmacy/cart/{hash}/order
     *
     * @param string $hash Prescription hash.
     */
    public function create_order(string $hash): void
    {
        try {
            $prescription = $this->prescriptions_model->find_by_hash($hash);

            $request = request();

            if (empty($request['customerEmail'])) {
                throw new InvalidArgumentException('Customer email is required.');
            }

            if (empty($request['fulfillmentMethod'])) {
                throw new InvalidArgumentException('Fulfillment method is required.');
            }

            // Get prescription items and calculate total from snapshots.
            $prescription_items = $this->prescription_items_model->get_by_prescription($prescription['id']);

            if (empty($prescription_items)) {
                throw new InvalidArgumentException('No items found in the prescription.');
            }

            $subtotal = 0;

            foreach ($prescription_items as $item) {
                $subtotal += (float) $item['medicine_price'] * (int) $item['quantity'];
            }

            $subtotal = round($subtotal, 2);
            $total = $subtotal;

            // Create the order.
            $order_data = [
                'id_prescriptions' => $prescription['id'],
                'customer_first_name' => $request['customerFirstName'] ?? '',
                'customer_last_name' => $request['customerLastName'] ?? '',
                'customer_email' => $request['customerEmail'],
                'customer_phone' => $request['customerPhone'] ?? null,
                'fulfillment_method' => $request['fulfillmentMethod'],
                'delivery_address' => $request['deliveryAddress'] ?? null,
                'id_branches' => !empty($request['branchId']) ? (int) $request['branchId'] : null,
                'subtotal' => $subtotal,
                'total' => $total,
                'currency' => 'AED',
                'status' => 'pending',
                'payment_status' => 'pending',
            ];

            $order_id = $this->pharmacy_orders_model->save($order_data);

            // Create order items from prescription item snapshots.
            foreach ($prescription_items as $p_item) {
                $line_total = round((float) $p_item['medicine_price'] * (int) $p_item['quantity'], 2);

                $this->pharmacy_order_items_model->save([
                    'id_pharmacy_orders' => $order_id,
                    'id_medicines' => (int) $p_item['id_medicines'],
                    'medicine_name_en' => $p_item['medicine_name_en'],
                    'medicine_name_ar' => $p_item['medicine_name_ar'],
                    'medicine_price' => (float) $p_item['medicine_price'],
                    'medicine_unit' => $p_item['medicine_unit'],
                    'quantity' => (int) $p_item['quantity'],
                    'dosage_notes' => $p_item['dosage_notes'] ?? null,
                    'line_total' => $line_total,
                ]);
            }

            // Create Stripe checkout session.
            $order = $this->pharmacy_orders_model->find($order_id);

            $stripe_data = [
                'amount' => $total,
                'currency' => 'AED',
                'customer_email' => $request['customerEmail'],
                'metadata' => [
                    'type' => 'pharmacy_order',
                    'order_id' => (string) $order_id,
                    'prescription_id' => (string) $prescription['id'],
                ],
                'success_url' => $request['successUrl'] ?? config('stripe_success_url'),
                'cancel_url' => $request['cancelUrl'] ?? config('stripe_cancel_url'),
            ];

            $stripe_result = $this->stripe_payment->create_checkout_session($stripe_data);

            // Update order with Stripe session ID.
            $this->pharmacy_orders_model->save([
                'id' => $order_id,
                'stripe_session_id' => $stripe_result['session_id'],
            ]);

            json_response([
                'orderId' => $order_id,
                'orderHash' => $order['hash'],
                'sessionId' => $stripe_result['session_id'],
                'checkoutUrl' => $stripe_result['checkout_url'],
                'total' => $total,
                'currency' => 'AED',
            ], 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Verify Stripe payment and update order.
     *
     * POST /api/v1/pharmacy/cart/verify-payment
     */
    public function verify_payment(): void
    {
        try {
            $request = request();

            if (empty($request['sessionId'])) {
                throw new InvalidArgumentException('Session ID is required.');
            }

            $session_id = $request['sessionId'];
            $is_paid = $this->stripe_payment->verify_payment($session_id);

            if (!$is_paid) {
                json_response(['status' => 'pending']);

                return;
            }

            $session = $this->stripe_payment->get_session($session_id);

            // Find the order by Stripe session ID.
            $orders = $this->pharmacy_orders_model->get(['stripe_session_id' => $session_id]);

            if (empty($orders)) {
                throw new InvalidArgumentException('No pharmacy order found for this session.');
            }

            $order = $orders[0];

            // Update order payment status.
            $this->pharmacy_orders_model->save([
                'id' => $order['id'],
                'payment_status' => 'paid',
                'payment_amount' => $session->amount_total / 100,
                'stripe_payment_intent_id' => $session->payment_intent,
                'paid_at' => date('Y-m-d H:i:s'),
                'status' => 'processing',
            ]);

            // Send confirmation email.
            try {
                $updated_order = $this->pharmacy_orders_model->find($order['id']);
                $order_items = $this->pharmacy_order_items_model->get_by_order($order['id']);

                foreach ($order_items as &$item) {
                    $this->pharmacy_order_items_model->api_encode($item);
                }

                // Determine language from customer's preferred language.
                $language = 'en';

                if (!empty($updated_order['id_prescriptions'])) {
                    try {
                        $prescription = $this->prescriptions_model->find((int) $updated_order['id_prescriptions']);

                        if (!empty($prescription['id_users_customer'])) {
                            $customer = $this->customers_model->find((int) $prescription['id_users_customer']);
                            $language = $customer['preferred_language'] ?? 'en';
                        }
                    } catch (Throwable $e) {
                        log_message('error', 'Could not determine customer language: ' . $e->getMessage());
                    }
                }

                $this->multilingual_notifications->send_pharmacy_order_confirmation(
                    $updated_order,
                    $order_items,
                    $language,
                );
            } catch (Throwable $e) {
                log_message('error', 'Failed to send pharmacy order confirmation email: ' . $e->getMessage());
            }

            json_response([
                'status' => 'paid',
                'orderId' => $order['id'],
                'paymentIntentId' => $session->payment_intent,
                'amountTotal' => $session->amount_total / 100,
                'currency' => strtoupper($session->currency),
            ]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
