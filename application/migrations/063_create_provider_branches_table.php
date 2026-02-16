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

class Migration_Create_provider_branches_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('provider_branches')) {
            $this->dbforge->add_field([
                'id_users_provider' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                ],
                'id_branches' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                ],
            ]);

            $this->dbforge->add_key(['id_users_provider', 'id_branches'], true);
            $this->dbforge->create_table('provider_branches', true, ['engine' => 'InnoDB']);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('provider_branches') .
                    '
                ADD CONSTRAINT fk_provider_branches_provider
                FOREIGN KEY (id_users_provider) REFERENCES ' .
                    $this->db->dbprefix('users') .
                    '(id)
                ON DELETE CASCADE ON UPDATE CASCADE',
            );

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('provider_branches') .
                    '
                ADD CONSTRAINT fk_provider_branches_branch
                FOREIGN KEY (id_branches) REFERENCES ' .
                    $this->db->dbprefix('branches') .
                    '(id)
                ON DELETE CASCADE ON UPDATE CASCADE',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('provider_branches')) {
            $this->dbforge->drop_table('provider_branches');
        }
    }
}
