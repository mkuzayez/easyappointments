<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_doctor_profile_fields extends EA_Migration
{
    public function up(): void
    {
        $fields = [
            'photo' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => true,
                'after' => 'notes',
            ],
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'photo',
            ],
            'qualifications' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'bio',
            ],
            'specialty' => [
                'type' => 'VARCHAR',
                'constraint' => '256',
                'null' => true,
                'after' => 'qualifications',
            ],
        ];

        $this->dbforge->add_column('users', $fields);
    }

    public function down(): void
    {
        $this->dbforge->drop_column('users', 'photo');
        $this->dbforge->drop_column('users', 'bio');
        $this->dbforge->drop_column('users', 'qualifications');
        $this->dbforge->drop_column('users', 'specialty');
    }
}
