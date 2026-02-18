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

class Migration_Create_prescription_items_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $original_prefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            if (!$this->db->table_exists('prescription_items')) {
                $this->dbforge->add_field([
                    'id' => [
                        'type' => 'INT',
                        'auto_increment' => true,
                    ],
                    'id_prescriptions' => [
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
                    'create_datetime' => [
                        'type' => 'DATETIME',
                    ],
                    'update_datetime' => [
                        'type' => 'DATETIME',
                    ],
                ]);

                $this->dbforge->add_key('id', true);
                $this->dbforge->add_key('id_prescriptions');
                $this->dbforge->add_key('id_medicines');
                $this->dbforge->create_table('prescription_items', true, ['engine' => 'InnoDB']);

                $this->db->query(
                    'ALTER TABLE `prescription_items`
                    ADD CONSTRAINT `fk_prescription_items_prescription`
                        FOREIGN KEY (`id_prescriptions`)
                        REFERENCES `prescriptions` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    ADD CONSTRAINT `fk_prescription_items_medicine`
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
            if ($this->db->table_exists('prescription_items')) {
                $this->dbforge->drop_table('prescription_items');
            }
        } finally {
            $this->db->dbprefix = $original_prefix;
        }
    }
}
