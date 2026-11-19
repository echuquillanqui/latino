<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAuditDateIndexToOrders extends Migration
{
    public function up()
    {
        DB::statement('CREATE INDEX orders_date_order_audit_idx ON orders (date_order(10))');
    }

    public function down()
    {
        DB::statement('DROP INDEX orders_date_order_audit_idx ON orders');
    }
}
