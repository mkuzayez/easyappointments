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

class Migration_Add_refund_fields_to_appointments extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('refund_amount', 'appointments')) {
            $fields = [
                'refund_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'after' => 'payment_currency',
                ],
                'refund_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => '20',
                    'default' => 'none',
                    'after' => 'refund_amount',
                ],
                'refund_reason' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'refund_status',
                ],
            ];

            $this->dbforge->add_column('appointments', $fields);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('appointments') .
                    ' ADD INDEX idx_refund_status (refund_status)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('refund_amount', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'refund_amount');
        }

        if ($this->db->field_exists('refund_status', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'refund_status');
        }

        if ($this->db->field_exists('refund_reason', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'refund_reason');
        }
    }
}
