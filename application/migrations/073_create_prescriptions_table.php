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

class Migration_Create_prescriptions_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $original_prefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            if (!$this->db->table_exists('prescriptions')) {
                $this->dbforge->add_field([
                    'id' => [
                        'type' => 'INT',
                        'auto_increment' => true,
                    ],
                    'hash' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64,
                    ],
                    'id_appointments' => [
                        'type' => 'INT',
                    ],
                    'id_users_provider' => [
                        'type' => 'INT',
                    ],
                    'id_users_customer' => [
                        'type' => 'INT',
                    ],
                    'notes' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                    'prescribed_date' => [
                        'type' => 'DATETIME',
                    ],
                    'create_datetime' => [
                        'type' => 'DATETIME',
                    ],
                    'update_datetime' => [
                        'type' => 'DATETIME',
                    ],
                ]);

                $this->dbforge->add_key('id', true);
                $this->dbforge->add_key('hash');
                $this->dbforge->add_key('id_appointments');
                $this->dbforge->add_key('id_users_provider');
                $this->dbforge->add_key('id_users_customer');
                $this->dbforge->create_table('prescriptions', true, ['engine' => 'InnoDB']);

                $this->db->query(
                    'ALTER TABLE `prescriptions`
                    ADD UNIQUE INDEX `idx_prescriptions_hash` (`hash`),
                    ADD CONSTRAINT `fk_prescriptions_appointments`
                        FOREIGN KEY (`id_appointments`)
                        REFERENCES `ea_appointments` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    ADD CONSTRAINT `fk_prescriptions_provider`
                        FOREIGN KEY (`id_users_provider`)
                        REFERENCES `ea_users` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    ADD CONSTRAINT `fk_prescriptions_customer`
                        FOREIGN KEY (`id_users_customer`)
                        REFERENCES `ea_users` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE'
                );
            }
        } finally {
            $this->db->dbprefix = $original_prefix;
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        $original_prefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            if ($this->db->table_exists('prescriptions')) {
                $this->dbforge->drop_table('prescriptions');
            }
        } finally {
            $this->db->dbprefix = $original_prefix;
        }
    }
}
