<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicePtplTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_ptpl', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('device_id');
            $table->integer('ptpl_id');
            $table->timestamps();
            $table->softDeletes(); //添加软删除
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_ptpl');
    }
}
