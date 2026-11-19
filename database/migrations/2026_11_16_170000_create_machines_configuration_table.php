<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMachinesConfigurationTable extends Migration
{
    public function up()
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('equipment', 80)->unique();
            $table->string('brand', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('module', 80)->nullable();
            $table->string('position', 80)->nullable();
            $table->timestamps();
        });

        $data = [];
        for ($number = 1; $number <= 15; $number++) {
            $data[] = [
                'equipment' => sprintf('MAQUINA Nº %02d', $number),
                'brand' => $number <= 8 ? 'DORA' : 'NIPRO',
                'model' => $number <= 8 ? '6000' : 'DIAMAX',
                'module' => $number <= 5 ? 'MODULO I' : ($number <= 9 ? 'MODULO II' : 'MODULO III'),
                'position' => 'PUESTO ' . $number,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('machines')->insert($data);
    }

    public function down()
    {
        Schema::dropIfExists('machines');
    }
}
