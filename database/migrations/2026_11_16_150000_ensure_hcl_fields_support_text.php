<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureHclFieldsSupportText extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('patients', 'hcl')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('hcl', 25)->nullable();
            });
        }

        DB::statement('ALTER TABLE nurses MODIFY hcl VARCHAR(20) NULL');
    }

    public function down()
    {
        // Keep stored clinical identifiers intact when rolling back this schema normalization.
    }
}
