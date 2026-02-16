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

class Migration_Add_branch_to_services extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('id_branches', 'services')) {
            $fields = [
                'id_branches' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'id_service_categories',
                ],
            ];

            $this->dbforge->add_column('services', $fields);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('services') .
                    '
                ADD CONSTRAINT fk_services_branches
                FOREIGN KEY (id_branches) REFERENCES ' .
                    $this->db->dbprefix('branches') .
                    '(id)
                ON DELETE SET NULL ON UPDATE CASCADE',
            );

            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('services') . ' ADD INDEX idx_branches (id_branches)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('id_branches', 'services')) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->dbprefix('services') . ' DROP FOREIGN KEY fk_services_branches',
            );
            $this->dbforge->drop_column('services', 'id_branches');
        }
    }
}
