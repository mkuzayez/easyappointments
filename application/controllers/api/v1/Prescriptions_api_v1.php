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
 * Prescriptions API v1 controller.
 *
 * @package Controllers
 */
class Prescriptions_api_v1 extends EA_Controller
{
    /**
     * Prescriptions_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('prescriptions_model');
        $this->load->model('prescription_items_model');
        $this->load->model('medicines_model');
        $this->load->model('appointments_model');
        $this->load->model('customers_model');
        $this->load->model('providers_model');
        $this->load->library('api');
        $this->load->library('multilingual_notifications');

        $this->api->auth();

        $this->api->model('prescriptions_model');
    }

    /**
     * Get a prescription collection.
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

            $prescriptions = $this->prescriptions_model->get(
                !empty($where) ? $where : null,
                $limit,
                $offset,
                $order_by,
            );

            foreach ($prescriptions as &$prescription) {
                $items = $this->prescription_items_model->get_by_prescription($prescription['id']);

                foreach ($items as &$item) {
                    $this->prescription_items_model->api_encode($item);
                }

                $this->prescriptions_model->api_encode($prescription);

                $prescription['items'] = $items;

                if (!empty($fields)) {
                    $this->prescriptions_model->only($prescription, $fields);
                }
            }

            json_response($prescriptions);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single prescription.
     *
     * @param int|null $id Prescription ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->prescriptions_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $prescription = $this->prescriptions_model->find($id);

            $items = $this->prescription_items_model->get_by_prescription($id);

            foreach ($items as &$item) {
                $this->prescription_items_model->api_encode($item);
            }

            $this->prescriptions_model->api_encode($prescription);

            $prescription['items'] = $items;

            if (!empty($fields)) {
                $this->prescriptions_model->only($prescription, $fields);
            }

            json_response($prescription);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new prescription with items.
     *
     * Expects JSON body:
     * {
     *   "appointmentId": 123,
     *   "notes": "General notes",
     *   "items": [
     *     { "medicineId": 1, "quantity": 2, "dosageNotes": "..." },
     *     ...
     *   ]
     * }
     */
    public function store(): void
    {
        try {
            $request = request();

            if (empty($request['appointmentId'])) {
                throw new InvalidArgumentException('The appointmentId is required.');
            }

            if (empty($request['items']) || !is_array($request['items'])) {
                throw new InvalidArgumentException('At least one prescription item is required.');
            }

            // Validate appointment exists and status is 'Completed'.
            $appointment = $this->appointments_model->find((int) $request['appointmentId']);

            if (empty($appointment['status']) || $appointment['status'] !== 'Completed') {
                throw new InvalidArgumentException(
                    'Prescriptions can only be created for appointments with status "Completed".',
                );
            }

            $provider_id = (int) $appointment['id_users_provider'];
            $customer_id = (int) $appointment['id_users_customer'];

            // Insert prescription.
            $prescription_data = [
                'id_appointments' => (int) $request['appointmentId'],
                'id_users_provider' => $provider_id,
                'id_users_customer' => $customer_id,
                'notes' => $request['notes'] ?? null,
            ];

            $prescription_id = $this->prescriptions_model->save($prescription_data);

            // Insert items with snapshots.
            $created_items = [];

            foreach ($request['items'] as $item) {
                if (empty($item['medicineId'])) {
                    throw new InvalidArgumentException('Each item must have a medicineId.');
                }

                $medicine = $this->medicines_model->find((int) $item['medicineId']);

                if (!$medicine['is_active'] || $medicine['stock_status'] !== 'in_stock') {
                    throw new InvalidArgumentException(
                        'Medicine is not available: ' . ($medicine['name_en'] ?? $item['medicineId']),
                    );
                }

                $item_data = [
                    'id_prescriptions' => $prescription_id,
                    'id_medicines' => (int) $item['medicineId'],
                    'medicine_name_en' => $medicine['name_en'],
                    'medicine_name_ar' => $medicine['name_ar'],
                    'medicine_price' => (float) $medicine['price'],
                    'medicine_unit' => $medicine['unit'],
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'dosage_notes' => $item['dosageNotes'] ?? null,
                ];

                $item_id = $this->prescription_items_model->save($item_data);

                $created_item = $this->prescription_items_model->find($item_id);

                $this->prescription_items_model->api_encode($created_item);

                $created_items[] = $created_item;
            }

            // Load the created prescription.
            $prescription = $this->prescriptions_model->find($prescription_id);

            $cart_url = site_url('pharmacy/cart/' . $prescription['hash']);

            // Send prescription email.
            try {
                $provider = $this->providers_model->find($provider_id);
                $customer = $this->customers_model->find($customer_id);

                $this->multilingual_notifications->send_prescription_ready(
                    $prescription,
                    $created_items,
                    $provider,
                    $customer,
                    $cart_url,
                );
            } catch (Throwable $e) {
                log_message('error', 'Failed to send prescription email: ' . $e->getMessage());
            }

            $this->prescriptions_model->api_encode($prescription);

            $prescription['items'] = $created_items;
            $prescription['cartUrl'] = $cart_url;

            json_response($prescription, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a prescription.
     *
     * @param int $id Prescription ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->prescriptions_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_prescription = $occurrences[0];

            $prescription = request();

            $this->prescriptions_model->api_decode($prescription, $original_prescription);

            $prescription_id = $this->prescriptions_model->save($prescription);

            $updated_prescription = $this->prescriptions_model->find($prescription_id);

            $items = $this->prescription_items_model->get_by_prescription($prescription_id);

            foreach ($items as &$item) {
                $this->prescription_items_model->api_encode($item);
            }

            $this->prescriptions_model->api_encode($updated_prescription);

            $updated_prescription['items'] = $items;

            json_response($updated_prescription);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete a prescription.
     *
     * @param int $id Prescription ID.
     */
    public function destroy(int $id): void
    {
        try {
            $occurrences = $this->prescriptions_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $this->prescriptions_model->delete($id);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
