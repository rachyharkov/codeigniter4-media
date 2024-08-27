<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedia extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'model_type' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ],
            'model_id' => [
                'type' => 'BIGINT',
                'constraint' => 100,
                'null' => true
            ],
            'unique_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'collection_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'file_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'file_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_size' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_ext' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'orig_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ],
            'custom_properties' => [
                'type' => 'TEXT',
                'null' => true
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'delete_at' => [
                'type' => 'DATETIME',
                'null' => true
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid');
        $this->forge->createTable('medias');
    }

    public function down()
    {
        $this->forge->dropTable('medias');
    }
}
