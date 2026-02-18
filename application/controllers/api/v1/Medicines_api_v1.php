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
 * Medicines API v1 controller.
 *
 * @package Controllers
 */
class Medicines_api_v1 extends EA_Controller
{
    /**
     * Medicines_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('medicines_model');
        $this->load->library('api');

        $this->api->auth();

        $this->api->model('medicines_model');
    }

    /**
     * Get a medicine collection.
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

            // Shortcut for prescription builder: active and in-stock only.
            $active_and_in_stock = request('activeAndInStock');

            if ($active_and_in_stock !== null && filter_var($active_and_in_stock, FILTER_VALIDATE_BOOLEAN)) {
                $category_id = request('categoryId');
                $category_id = $category_id !== null ? (int) $category_id : null;

                $medicines = $this->medicines_model->get_active_in_stock($category_id);

                foreach ($medicines as &$medicine) {
                    $this->medicines_model->api_encode($medicine);

                    if (!empty($fields)) {
                        $this->medicines_model->only($medicine, $fields);
                    }

                    if (!empty($with)) {
                        $this->medicines_model->load($medicine, $with);
                    }
                }

                json_response($medicines);

                return;
            }

            $where = [];

            $category_id = request('categoryId');

            if ($category_id !== null) {
                $where['id_medicine_categories'] = (int) $category_id;
            }

            $is_active = request('isActive');

            if ($is_active !== null) {
                $where['is_active'] = filter_var($is_active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            $medicines = empty($keyword)
                ? $this->medicines_model->get(!empty($where) ? $where : null, $limit, $offset, $order_by)
                : $this->medicines_model->search($keyword, $limit, $offset, $order_by);

            foreach ($medicines as &$medicine) {
                $this->medicines_model->api_encode($medicine);

                if (!empty($fields)) {
                    $this->medicines_model->only($medicine, $fields);
                }

                if (!empty($with)) {
                    $this->medicines_model->load($medicine, $with);
                }
            }

            json_response($medicines);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single medicine.
     *
     * @param int|null $id Medicine ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->medicines_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $with = $this->api->request_with();

            $medicine = $this->medicines_model->find($id);

            $this->medicines_model->api_encode($medicine);

            if (!empty($fields)) {
                $this->medicines_model->only($medicine, $fields);
            }

            if (!empty($with)) {
                $this->medicines_model->load($medicine, $with);
            }

            json_response($medicine);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new medicine.
     */
    public function store(): void
    {
        try {
            $medicine = request();

            $this->medicines_model->api_decode($medicine);

            if (array_key_exists('id', $medicine)) {
                unset($medicine['id']);
            }

            $medicine_id = $this->medicines_model->save($medicine);

            $created_medicine = $this->medicines_model->find($medicine_id);

            $this->medicines_model->api_encode($created_medicine);

            json_response($created_medicine, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a medicine.
     *
     * @param int $id Medicine ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->medicines_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_medicine = $occurrences[0];

            $medicine = request();

            $this->medicines_model->api_decode($medicine, $original_medicine);

            $medicine_id = $this->medicines_model->save($medicine);

            $updated_medicine = $this->medicines_model->find($medicine_id);

            $this->medicines_model->api_encode($updated_medicine);

            json_response($updated_medicine);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Soft-delete a medicine (sets is_active=0).
     *
     * @param int $id Medicine ID.
     */
    public function destroy(int $id): void
    {
        try {
            $occurrences = $this->medicines_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $this->medicines_model->delete($id);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
