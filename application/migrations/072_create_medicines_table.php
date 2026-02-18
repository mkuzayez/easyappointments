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

class Migration_Create_medicines_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $original_prefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            if (!$this->db->table_exists('medicines')) {
                $this->dbforge->add_field([
                    'id' => [
                        'type' => 'INT',
                        'auto_increment' => true,
                    ],
                    'id_medicine_categories' => [
                        'type' => 'INT',
                    ],
                    'name_en' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'name_ar' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'description' => [
                        'type' => 'TEXT',
                        'null' => true,
                    ],
                    'price' => [
                        'type' => 'DECIMAL',
                        'constraint' => '10,2',
                    ],
                    'unit' => [
                        'type' => "ENUM('tablet','capsule','ml','mg','sachet','tube','bottle','piece')",
                        'default' => 'tablet',
                    ],
                    'stock_status' => [
                        'type' => "ENUM('in_stock','out_of_stock')",
                        'default' => 'in_stock',
                    ],
                    'is_active' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 1,
                    ],
                    'create_datetime' => [
                        'type' => 'DATETIME',
                    ],
                    'update_datetime' => [
                        'type' => 'DATETIME',
                    ],
                ]);

                $this->dbforge->add_key('id', true);
                $this->dbforge->add_key('id_medicine_categories');
                $this->dbforge->add_key('is_active');
                $this->dbforge->add_key('stock_status');
                $this->dbforge->create_table('medicines', true, ['engine' => 'InnoDB']);

                $this->db->query(
                    'ALTER TABLE `medicines`
                    ADD CONSTRAINT `fk_medicines_category`
                        FOREIGN KEY (`id_medicine_categories`)
                        REFERENCES `medicine_categories` (`id`)
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
            if ($this->db->table_exists('medicines')) {
                $this->dbforge->drop_table('medicines');
            }
        } finally {
            $this->db->dbprefix = $original_prefix;
        }
    }
}
