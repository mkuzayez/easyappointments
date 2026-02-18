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

class Migration_Create_pharmacy_order_items_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $original_prefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            if (!$this->db->table_exists('pharmacy_order_items')) {
                $this->dbforge->add_field([
                    'id' => [
                        'type' => 'INT',
                        'auto_increment' => true,
                    ],
                    'id_pharmacy_orders' => [
                        'type' => 'INT',
                    ],
                    'id_medicines' => [
                        'type' => 'INT',
                    ],
                    'medicine_name_en' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'medicine_name_ar' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'medicine_price' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                    ],
                    'medicine_unit' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                    ],
                    'quantity' => [
                        'type' => 'INT',
                        'default' => 1,
                    ],
                    'dosage_notes' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                    'line_total' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                    ],
                    'create_datetime' => [
                        'type' => 'DATETIME',
                    ],
                    'update_datetime' => [
                        'type' => 'DATETIME',
                    ],
                ]);

                $this->dbforge->add_key('id', true);
                $this->dbforge->add_key('id_pharmacy_orders');
                $this->dbforge->add_key('id_medicines');
                $this->dbforge->create_table('pharmacy_order_items', true, ['engine' => 'InnoDB']);

                $this->db->query(
                    'ALTER TABLE `pharmacy_order_items`
                    ADD CONSTRAINT `fk_pharmacy_order_items_order`
                        FOREIGN KEY (`id_pharmacy_orders`)
                        REFERENCES `pharmacy_orders` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    ADD CONSTRAINT `fk_pharmacy_order_items_medicine`
                        FOREIGN KEY (`id_medicines`)
                        REFERENCES `medicines` (`id`)
                        ON DELETE RESTRICT ON UPDATE CASCADE'
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
            if ($this->db->table_exists('pharmacy_order_items')) {
                $this->dbforge->drop_table('pharmacy_order_items');
            }
        } finally {
            $this->db->dbprefix = $original_prefix;
        }
    }
}
