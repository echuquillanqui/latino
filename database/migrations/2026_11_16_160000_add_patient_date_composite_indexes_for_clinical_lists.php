<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPatientDateCompositeIndexesForClinicalLists extends Migration
{
    public function up()
    {
        DB::statement('CREATE INDEX nurses_patient_date_id_idx ON nurses (patient(100), date_order(10), id)');
        DB::statement('CREATE INDEX medicals_patient_date_id_idx ON medicals (patient(100), date_order(10), id)');
    }

    public function down()
    {
        DB::statement('DROP INDEX nurses_patient_date_id_idx ON nurses');
        DB::statement('DROP INDEX medicals_patient_date_id_idx ON medicals');
    }
}
