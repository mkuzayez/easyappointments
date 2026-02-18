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

class Migration_Create_patient_feedback_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('patient_feedback')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'INT',
                    'auto_increment' => true,
                ],
                'id_appointments' => [
                    'type' => 'INT',
                ],
                'id_users_customer' => [
                    'type' => 'INT',
                ],
                'id_users_provider' => [
                    'type' => 'INT',
                ],
                'rating' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                ],
                'feedback_text' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'feedback_category' => [
                    'type' => 'ENUM("service","doctor","facility","overall")',
                    'default' => 'overall',
                ],
                'is_approved' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'submitted_date' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'approved_date' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'create_datetime' => [
                    'type' => 'DATETIME',
                ],
                'update_datetime' => [
                    'type' => 'DATETIME',
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('id_appointments');
            $this->dbforge->add_key('id_users_provider');
            $this->dbforge->add_key('rating');
            $this->dbforge->add_key('is_approved');
            $this->dbforge->create_table('patient_feedback', true, ['engine' => 'InnoDB']);

            $prefix = $this->db->dbprefix;

            $this->db->query(
                'ALTER TABLE `' . $prefix . 'patient_feedback`
                ADD CONSTRAINT `fk_patient_feedback_appointments`
                    FOREIGN KEY (`id_appointments`)
                    REFERENCES `' . $prefix . 'appointments` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `fk_patient_feedback_customer`
                    FOREIGN KEY (`id_users_customer`)
                    REFERENCES `' . $prefix . 'users` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `fk_patient_feedback_provider`
                    FOREIGN KEY (`id_users_provider`)
                    REFERENCES `' . $prefix . 'users` (`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE'
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('patient_feedback')) {
            $this->dbforge->drop_table('patient_feedback');
        }
    }
}
