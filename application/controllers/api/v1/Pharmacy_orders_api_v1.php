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
 * Pharmacy Orders API v1 controller.
 *
 * Authenticated endpoints for the pharmacy dashboard.
 *
 * @package Controllers
 */
class Pharmacy_orders_api_v1 extends EA_Controller
{
    /**
     * Pharmacy_orders_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('pharmacy_orders_model');
        $this->load->model('pharmacy_order_items_model');
        $this->load->library('api');

        $this->api->auth();

        $this->api->model('pharmacy_orders_model');
    }

    /**
     * Get a pharmacy order collection.
     */
    public function index(): void
    {
        try {
            $limit = $this->api->request_limit();

            $offset = $this->api->request_offset();

            $order_by = $this->api->request_order_by();

            $fields = $this->api->request_fields();

            $where = [];

            $status = request('status');

            if ($status !== null) {
                $where['status'] = $status;
            }

            $payment_status = request('paymentStatus');

            if ($payment_status !== null) {
                $where['payment_status'] = $payment_status;
            }

            $orders = $this->pharmacy_orders_model->get(
                !empty($where) ? $where : null,
                $limit,
                $offset,
                $order_by,
            );

            foreach ($orders as &$order) {
                $items = $this->pharmacy_order_items_model->get_by_order($order['id']);

                foreach ($items as &$item) {
                    $this->pharmacy_order_items_model->api_encode($item);
                }

                $this->pharmacy_orders_model->api_encode($order);

                $order['items'] = $items;

                if (!empty($fields)) {
                    $this->pharmacy_orders_model->only($order, $fields);
                }
            }

            json_response($orders);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single pharmacy order.
     *
     * @param int|null $id Order ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->pharmacy_orders_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $order = $this->pharmacy_orders_model->find($id);

            $items = $this->pharmacy_order_items_model->get_by_order($id);

            foreach ($items as &$item) {
                $this->pharmacy_order_items_model->api_encode($item);
            }

            $this->pharmacy_orders_model->api_encode($order);

            $order['items'] = $items;

            if (!empty($fields)) {
                $this->pharmacy_orders_model->only($order, $fields);
            }

            json_response($order);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a pharmacy order's fulfillment status.
     *
     * PATCH /api/v1/pharmacy_orders/{id}/status
     *
     * @param int $id Order ID.
     */
    public function update_status(int $id): void
    {
        try {
            $occurrences = $this->pharmacy_orders_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $request = request();

            if (empty($request['status'])) {
                throw new InvalidArgumentException('The status field is required.');
            }

            $allowed_statuses = ['pending', 'processing', 'delivered', 'ready_for_pickup', 'cancelled'];

            if (!in_array($request['status'], $allowed_statuses, true)) {
                throw new InvalidArgumentException('Invalid status value: ' . $request['status']);
            }

            $this->pharmacy_orders_model->save([
                'id' => $id,
                'status' => $request['status'],
            ]);

            $order = $this->pharmacy_orders_model->find($id);

            $items = $this->pharmacy_order_items_model->get_by_order($id);

            foreach ($items as &$item) {
                $this->pharmacy_order_items_model->api_encode($item);
            }

            $this->pharmacy_orders_model->api_encode($order);

            $order['items'] = $items;

            json_response($order);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
