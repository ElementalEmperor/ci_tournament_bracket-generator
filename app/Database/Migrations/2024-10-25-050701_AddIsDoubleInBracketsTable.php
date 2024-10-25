<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsDoubleInBracketsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('brackets', [
            'is_double' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('brackets', 'is_double');
    }
}