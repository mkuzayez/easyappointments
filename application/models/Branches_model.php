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
 * Branches model.
 *
 * Handles all the database operations of the branch resource.
 *
 * @package Models
 */
class Branches_model extends EA_Model
{
    /**
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @var array
     */
    protected array $api_resource = [
        'id' => 'id',
        'name' => 'name',
        'nameAr' => 'name_ar',
        'address' => 'address',
        'phone' => 'phone',
        'email' => 'email',
        'city' => 'city',
        'isActive' => 'is_active',
    ];

    /**
     * Save (insert or update) a branch.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @return int Returns the branch ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $branch): int
    {
        $this->validate($branch);

        if (empty($branch['id'])) {
            return $this->insert($branch);
        } else {
            return $this->update($branch);
        }
    }

    /**
     * Validate the branch data.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $branch): void
    {
        // If a branch ID is provided then check whether the record really exists in the database.
        if (!empty($branch['id'])) {
            $count = $this->db->get_where('branches', ['id' => $branch['id']])->num_rows();

            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided branch ID does not exist in the database: ' . $branch['id'],
                );
            }
        }

        // Make sure all required fields are provided.
        if (empty($branch['name'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($branch, true));
        }
    }

    /**
     * Insert a new branch into the database.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @return int Returns the branch ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $branch): int
    {
        $branch['create_datetime'] = date('Y-m-d H:i:s');
        $branch['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('branches', $branch)) {
            throw new RuntimeException('Could not insert branch.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing branch.
     *
     * @param array $branch Associative array with the branch data.
     *
     * @return int Returns the branch ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $branch): int
    {
        $branch['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->update('branches', $branch, ['id' => $branch['id']])) {
            throw new RuntimeException('Could not update branch.');
        }

        return $branch['id'];
    }

    /**
     * Remove an existing branch from the database.
     *
     * @param int $branch_id Branch ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $branch_id): void
    {
        $this->db->delete('branches', ['id' => $branch_id]);
    }

    /**
     * Get a specific branch from the database.
     *
     * @param int $branch_id The ID of the record to be returned.
     *
     * @return array Returns an array with the branch data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $branch_id): array
    {
        $branch = $this->db->get_where('branches', ['id' => $branch_id])->row_array();

        if (!$branch) {
            throw new InvalidArgumentException(
                'The provided branch ID was not found in the database: ' . $branch_id,
            );
        }

        $this->cast($branch);

        return $branch;
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $branch_id Branch ID.
     * @param string $field Name of the value to be returned.
     *
     * @return mixed Returns the selected branch value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $branch_id, string $field): mixed
    {
        if (empty($field)) {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($branch_id)) {
            throw new InvalidArgumentException('The branch ID argument cannot be empty.');
        }

        // Check whether the branch exists.
        $query = $this->db->get_where('branches', ['id' => $branch_id]);

        if (!$query->num_rows()) {
            throw new InvalidArgumentException(
                'The provided branch ID was not found in the database: ' . $branch_id,
            );
        }

        // Check if the required field is part of the branch data.
        $branch = $query->row_array();

        $this->cast($branch);

        if (!array_key_exists($field, $branch)) {
            throw new InvalidArgumentException('The requested field was not found in the branch data: ' . $field);
        }

        return $branch[$field];
    }

    /**
     * Get all branches that match the provided criteria.
     *
     * @param array|string|null $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of branches.
     */
    public function get(
        array|string|null $where = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $order_by = null,
    ): array {
        if ($where !== null) {
            $this->db->where($where);
        }

        if ($order_by !== null) {
            $this->db->order_by($this->quote_order_by($order_by));
        }

        $branches = $this->db->get('branches', $limit, $offset)->result_array();

        foreach ($branches as &$branch) {
            $this->cast($branch);
        }

        return $branches;
    }

    /**
     * Get the query builder interface, configured for use with the branches table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        return $this->db->from('branches');
    }

    /**
     * Search branches by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of branches.
     */
    public function search(string $keyword, ?int $limit = null, ?int $offset = null, ?string $order_by = null): array
    {
        $branches = $this->db
            ->select()
            ->from('branches')
            ->group_start()
            ->like('name', $keyword)
            ->or_like('name_ar', $keyword)
            ->or_like('address', $keyword)
            ->or_like('city', $keyword)
            ->or_like('phone', $keyword)
            ->or_like('email', $keyword)
            ->group_end()
            ->limit($limit)
            ->offset($offset)
            ->order_by($this->quote_order_by($order_by))
            ->get()
            ->result_array();

        foreach ($branches as &$branch) {
            $this->cast($branch);
        }

        return $branches;
    }

    /**
     * Load related resources to a branch.
     *
     * @param array $branch Associative array with the branch data.
     * @param array $resources Resource names to be attached.
     *
     * @throws InvalidArgumentException
     */
    public function load(array &$branch, array $resources): void
    {
        if (empty($branch) || empty($resources)) {
            return;
        }

        foreach ($resources as $resource) {
            match ($resource) {
                'providers' => $branch['providers'] = $this->db
                    ->select('users.*')
                    ->from('users')
                    ->join('provider_branches', 'provider_branches.id_users_provider = users.id', 'inner')
                    ->where('provider_branches.id_branches', $branch['id'])
                    ->get()
                    ->result_array(),
                'services' => $branch['services'] = $this->db
                    ->select('services.*')
                    ->from('services')
                    ->where('id_branches', $branch['id'])
                    ->get()
                    ->result_array(),
                default => throw new InvalidArgumentException(
                    'The requested branch relation is not supported: ' . $resource,
                ),
            };
        }
    }

    /**
     * Convert the database branch record to the equivalent API resource.
     *
     * @param array $branch Branch data.
     */
    public function api_encode(array &$branch): void
    {
        $encoded_resource = [
            'id' => array_key_exists('id', $branch) ? (int) $branch['id'] : null,
            'name' => $branch['name'],
            'nameAr' => $branch['name_ar'],
            'address' => $branch['address'],
            'phone' => $branch['phone'],
            'email' => $branch['email'],
            'city' => $branch['city'],
            'isActive' => (bool) $branch['is_active'],
        ];

        $branch = $encoded_resource;
    }

    /**
     * Convert the API resource to the equivalent database branch record.
     *
     * @param array $branch API resource.
     * @param array|null $base Base branch data to be overwritten with the provided values (useful for updates).
     */
    public function api_decode(array &$branch, ?array $base = null): void
    {
        $decoded_resource = $base ?: [];

        if (array_key_exists('id', $branch)) {
            $decoded_resource['id'] = $branch['id'];
        }

        if (array_key_exists('name', $branch)) {
            $decoded_resource['name'] = $branch['name'];
        }

        if (array_key_exists('nameAr', $branch)) {
            $decoded_resource['name_ar'] = $branch['nameAr'];
        }

        if (array_key_exists('address', $branch)) {
            $decoded_resource['address'] = $branch['address'];
        }

        if (array_key_exists('phone', $branch)) {
            $decoded_resource['phone'] = $branch['phone'];
        }

        if (array_key_exists('email', $branch)) {
            $decoded_resource['email'] = $branch['email'];
        }

        if (array_key_exists('city', $branch)) {
            $decoded_resource['city'] = $branch['city'];
        }

        if (array_key_exists('isActive', $branch)) {
            $decoded_resource['is_active'] = (bool) $branch['isActive'];
        }

        $branch = $decoded_resource;
    }
}
