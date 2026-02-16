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
 * Branches API v1 controller.
 *
 * @package Controllers
 */
class Branches_api_v1 extends EA_Controller
{
    /**
     * Branches_api_v1 constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api');

        $this->api->auth();

        $this->api->model('branches_model');
    }

    /**
     * Get a branch collection.
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

            // Filter by active status.
            $is_active = request('isActive');

            if ($is_active !== null) {
                $where['is_active'] = filter_var($is_active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            $branches = empty($keyword)
                ? $this->branches_model->get($where, $limit, $offset, $order_by)
                : $this->branches_model->search($keyword, $limit, $offset, $order_by);

            foreach ($branches as &$branch) {
                $this->branches_model->api_encode($branch);

                if (!empty($fields)) {
                    $this->branches_model->only($branch, $fields);
                }

                if (!empty($with)) {
                    $this->branches_model->load($branch, $with);
                }
            }

            json_response($branches);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Get a single branch.
     *
     * @param int|null $id Branch ID.
     */
    public function show(?int $id = null): void
    {
        try {
            $occurrences = $this->branches_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $fields = $this->api->request_fields();

            $with = $this->api->request_with();

            $branch = $this->branches_model->find($id);

            $this->branches_model->api_encode($branch);

            if (!empty($fields)) {
                $this->branches_model->only($branch, $fields);
            }

            if (!empty($with)) {
                $this->branches_model->load($branch, $with);
            }

            json_response($branch);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Store a new branch.
     */
    public function store(): void
    {
        try {
            $branch = request();

            $this->branches_model->api_decode($branch);

            if (array_key_exists('id', $branch)) {
                unset($branch['id']);
            }

            $branch_id = $this->branches_model->save($branch);

            $created_branch = $this->branches_model->find($branch_id);

            $this->branches_model->api_encode($created_branch);

            json_response($created_branch, 201);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Update a branch.
     *
     * @param int $id Branch ID.
     */
    public function update(int $id): void
    {
        try {
            $occurrences = $this->branches_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $original_branch = $occurrences[0];

            $branch = request();

            $this->branches_model->api_decode($branch, $original_branch);

            $branch_id = $this->branches_model->save($branch);

            $updated_branch = $this->branches_model->find($branch_id);

            $this->branches_model->api_encode($updated_branch);

            json_response($updated_branch);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete a branch.
     *
     * @param int $id Branch ID.
     */
    public function destroy(int $id): void
    {
        try {
            $occurrences = $this->branches_model->get(['id' => $id]);

            if (empty($occurrences)) {
                response('', 404);

                return;
            }

            $this->branches_model->delete($id);

            response('', 204);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }
}
