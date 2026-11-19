<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddNurseMedicalSearchIndexes extends Migration
{
    public function up()
    {
        DB::statement('CREATE INDEX nurses_patient_search_idx ON nurses (patient(100))');
        DB::statement('CREATE INDEX nurses_date_shift_search_idx ON nurses (date_order(10), shift(50))');
        DB::statement('CREATE INDEX nurses_created_search_idx ON nurses (created_at)');

        DB::statement('CREATE INDEX medicals_patient_search_idx ON medicals (patient(100))');
        DB::statement('CREATE INDEX medicals_date_shift_hour_search_idx ON medicals (date_order(10), shift(50), hour_hd(20))');
        DB::statement('CREATE INDEX medicals_created_search_idx ON medicals (created_at)');
    }

    public function down()
    {
        DB::statement('DROP INDEX nurses_patient_search_idx ON nurses');
        DB::statement('DROP INDEX nurses_date_shift_search_idx ON nurses');
        DB::statement('DROP INDEX nurses_created_search_idx ON nurses');

        DB::statement('DROP INDEX medicals_patient_search_idx ON medicals');
        DB::statement('DROP INDEX medicals_date_shift_hour_search_idx ON medicals');
        DB::statement('DROP INDEX medicals_created_search_idx ON medicals');
    }
}
