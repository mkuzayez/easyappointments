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
 * Pharmacy cart controller.
 *
 * Renders the public pharmacy cart page for patients.
 * No authentication required — access is secured via prescription hash.
 *
 * @package Controllers
 */
class Pharmacy_cart extends EA_Controller
{
    /**
     * Display the pharmacy cart page.
     *
     * @param string|null $hash Prescription hash.
     */
    public function index(?string $hash = null): void
    {
        if (empty($hash)) {
            show_404();

            return;
        }

        $this->load->model('prescriptions_model');
        $this->load->model('prescription_items_model');
        $this->load->model('customers_model');
        $this->load->model('branches_model');

        try {
            $prescription = $this->prescriptions_model->find_by_hash($hash);

            $items = $this->prescription_items_model->get_by_prescription($prescription['id']);

            $subtotal = 0;

            foreach ($items as &$item) {
                $item['line_total'] = round((float) $item['medicine_price'] * (int) $item['quantity'], 2);
                $subtotal += $item['line_total'];
            }

            // Load customer info for pre-filling.
            $customer = null;

            try {
                $customer = $this->customers_model->find((int) $prescription['id_users_customer']);
            } catch (Throwable $e) {
                log_message('error', 'Pharmacy cart: Could not load customer: ' . $e->getMessage());
            }

            // Load branches for pickup option.
            $branches = $this->branches_model->get(['is_active' => 1]);

            $data = [
                'hash' => $hash,
                'prescription' => $prescription,
                'items' => $items,
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal, 2),
                'customer' => $customer,
                'branches' => $branches,
                'base_url' => config('base_url'),
                'stripe_publishable_key' => config('stripe_publishable_key'),
            ];

            $this->load->view('pages/pharmacy_cart', $data);
        } catch (Throwable $e) {
            log_message('error', 'Pharmacy cart error: ' . $e->getMessage());
            show_404();
        }
    }
}
