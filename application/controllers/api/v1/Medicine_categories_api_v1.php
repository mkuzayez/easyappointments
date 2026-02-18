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
 * Medicine Categories API v1 controller.
 *
 * @package Controllers
 */
class Medicine_categories_api_v1 extends EA_Controller
{
    /**
     * Medicine_categories_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('medicine_categories_model');
        $this->load->library('api');

        $this->api->auth();

        $this->api->model('medicine_categories_model');
    }

    /**
     * Get a medicine category collection.
     */
    public function index(): void
    {
        try {
            $keyword = $this->api->request_keyword();

            $limit = $this->api->request_limit();

            $offset = $this->api->request_offset();

            $order_by = $this->api->request_order_by();

            $fields = $this->api->request_fields();

            $where = null;

            $is_active = request('isActive');

            if ($is_active !== null) {
                $where['is_active'] = filter_var($is_active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            $categories = empty($keyword)
                ? $this->medicine_categories_model->get($where, $limit, $offset, $order_by)
                : $this->medicine_categories_model->search($keyword, $limit, $offset, $order_by);

            foreach ($categories as &$category) {
                $this->medicine_categories_model->api_encode($category);

                if (!empty($fields)) {
                    $this->medicine_categories_model->only($category, $fields);
                }
            }

            json_response($categories);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single medicine category.
     *
     * @param int|null $id Category ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->medicine_categories_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $category = $this->medicine_categories_model->find($id);

            $this->medicine_categories_model->api_encode($category);

            if (!empty($fields)) {
                $this->medicine_categories_model->only($category, $fields);
            }

            json_response($category);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new medicine category.
     */
    public function store(): void
    {
        try {
            $category = request();

            $this->medicine_categories_model->api_decode($category);

            if (array_key_exists('id', $category)) {
                unset($category['id']);
            }

            $category_id = $this->medicine_categories_model->save($category);

            $created_category = $this->medicine_categories_model->find($category_id);

            $this->medicine_categories_model->api_encode($created_category);

            json_response($created_category, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a medicine category.
     *
     * @param int $id Category ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->medicine_categories_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_category = $occurrences[0];

            $category = request();

            $this->medicine_categories_model->api_decode($category, $original_category);

            $category_id = $this->medicine_categories_model->save($category);

            $updated_category = $this->medicine_categories_model->find($category_id);

            $this->medicine_categories_model->api_encode($updated_category);

            json_response($updated_category);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete a medicine category.
     *
     * @param int $id Category ID.
     */
    public function destroy(int $id): void
    {
        try {
            $occurrences = $this->medicine_categories_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $this->medicine_categories_model->delete($id);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
