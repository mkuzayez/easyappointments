<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_preferred_language extends EA_Migration
{
    public function up(): void
    {
        $fields = [
            'preferred_language' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'default' => 'en',
                'after' => 'language',
            ],
        ];

        $this->dbforge->add_column('users', $fields);
    }

    public function down(): void
    {
        $this->dbforge->drop_column('users', 'preferred_language');
    }
}
