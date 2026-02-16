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

class Migration_Add_payment_fields_to_appointments extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->field_exists('payment_status', 'appointments')) {
            $fields = [
                'payment_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => '50',
                    'default' => 'pending',
                    'after' => 'status',
                ],
                'payment_method' => [
                    'type' => 'VARCHAR',
                    'constraint' => '50',
                    'null' => true,
                    'after' => 'payment_status',
                ],
                'stripe_session_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                    'after' => 'payment_method',
                ],
                'stripe_payment_intent_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                    'after' => 'stripe_session_id',
                ],
                'payment_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'after' => 'stripe_payment_intent_id',
                ],
                'payment_currency' => [
                    'type' => 'VARCHAR',
                    'constraint' => '10',
                    'default' => 'AED',
                    'after' => 'payment_amount',
                ],
            ];

            $this->dbforge->add_column('appointments', $fields);

            $this->db->query(
                'ALTER TABLE ' .
                    $this->db->dbprefix('appointments') .
                    '
                ADD INDEX idx_payment_status (payment_status),
                ADD INDEX idx_stripe_session (stripe_session_id)',
            );
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->field_exists('payment_status', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'payment_status');
        }

        if ($this->db->field_exists('payment_method', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'payment_method');
        }

        if ($this->db->field_exists('stripe_session_id', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'stripe_session_id');
        }

        if ($this->db->field_exists('stripe_payment_intent_id', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'stripe_payment_intent_id');
        }

        if ($this->db->field_exists('payment_amount', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'payment_amount');
        }

        if ($this->db->field_exists('payment_currency', 'appointments')) {
            $this->dbforge->drop_column('appointments', 'payment_currency');
        }
    }
}
