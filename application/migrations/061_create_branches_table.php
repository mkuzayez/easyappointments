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

class Migration_Create_branches_table extends EA_Migration
{
    /**
     * Upgrade method.
     */
    public function up(): void
    {
        if (!$this->db->table_exists('branches')) {
            $this->dbforge->add_field([
                'id' => [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                ],
                'name_ar' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
                'address' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'phone' => [
                    'type' => 'VARCHAR',
                    'constraint' => '50',
                    'null' => true,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
                'city' => [
                    'type' => 'VARCHAR',
                    'constraint' => '100',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 4,
                    'default' => '1',
                ],
                'create_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'update_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('is_active');
            $this->dbforge->create_table('branches', true, ['engine' => 'InnoDB']);

            // Seed default branches
            $this->db->insert('branches', [
                'name' => 'Dubai Branch',
                'name_ar' => 'فرع دبي',
                'city' => 'Dubai',
                'is_active' => 1,
                'create_datetime' => date('Y-m-d H:i:s'),
                'update_datetime' => date('Y-m-d H:i:s'),
            ]);

            $this->db->insert('branches', [
                'name' => 'Abu Dhabi Branch',
                'name_ar' => 'فرع أبو ظبي',
                'city' => 'Abu Dhabi',
                'is_active' => 1,
                'create_datetime' => date('Y-m-d H:i:s'),
                'update_datetime' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Downgrade method.
     */
    public function down(): void
    {
        if ($this->db->table_exists('branches')) {
            $this->dbforge->drop_table('branches');
        }
    }
}
