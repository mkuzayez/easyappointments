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

class Migration_Create_pharmacy_orders_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $original_prefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            if (!$this->db->table_exists('pharmacy_orders')) {
                $this->dbforge->add_field([
                    'id' => [
                        'type' => 'INT',
                        'auto_increment' => true,
                    ],
                    'hash' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64,
                    ],
                    'id_prescriptions' => [
                        'type' => 'INT',
                    ],
                    'customer_first_name' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'customer_last_name' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'customer_email' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'customer_phone' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                    'fulfillment_method' => [
                        'type' => "ENUM('home_delivery','in_clinic_pickup')",
                    ],
                    'delivery_address' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                    'id_branches' => [
                        'type' => 'BIGINT',
                        'unsigned' => true,
                        'null' => true,
                    ],
                    'status' => [
                        'type' => "ENUM('pending','processing','delivered','ready_for_pickup','cancelled')",
                        'default' => 'pending',
                    ],
                    'subtotal' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                    ],
                    'total' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                    ],
                    'currency' => [
                        'type' => 'VARCHAR',
                        'constraint' => 3,
                        'default' => 'AED',
                    ],
                    'stripe_session_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true,
                    ],
                    'stripe_payment_intent_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true,
                    ],
                    'payment_status' => [
                        'type' => "ENUM('pending','paid','refunded')",
                        'default' => 'pending',
                    ],
                    'payment_amount' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                        'null' => true,
                    ],
                    'paid_at' => [
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
                $this->dbforge->add_key('hash');
                $this->dbforge->add_key('id_prescriptions');
                $this->dbforge->add_key('status');
                $this->dbforge->add_key('payment_status');
                $this->dbforge->create_table('pharmacy_orders', true, ['engine' => 'InnoDB']);

                $this->db->query(
                    'ALTER TABLE `pharmacy_orders`
                    ADD UNIQUE INDEX `idx_pharmacy_orders_hash` (`hash`),
                    ADD CONSTRAINT `fk_pharmacy_orders_prescription`
                        FOREIGN KEY (`id_prescriptions`)
                        REFERENCES `prescriptions` (`id`)
                        ON DELETE RESTRICT ON UPDATE CASCADE,
                    ADD CONSTRAINT `fk_pharmacy_orders_branch`
                        FOREIGN KEY (`id_branches`)
                        REFERENCES `ea_branches` (`id`)
                        ON DELETE SET NULL ON UPDATE CASCADE'
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
            if ($this->db->table_exists('pharmacy_orders')) {
                $this->dbforge->drop_table('pharmacy_orders');
            }
        } finally {
            $this->db->dbprefix = $original_prefix;
        }
    }
}
