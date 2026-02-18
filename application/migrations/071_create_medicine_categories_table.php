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

class Migration_Create_medicine_categories_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        $original_prefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        try {
            if (!$this->db->table_exists('medicine_categories')) {
                $this->dbforge->add_field([
                    'id' => [
                        'type' => 'INT',
                        'auto_increment' => true,
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
                $this->dbforge->create_table('medicine_categories', true, ['engine' => 'InnoDB']);
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
            if ($this->db->table_exists('medicine_categories')) {
                $this->dbforge->drop_table('medicine_categories');
            }
        } finally {
            $this->db->dbprefix = $original_prefix;
        }
    }
}
