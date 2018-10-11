<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('uid');
            $table->string('type');
            $table->string('brand');
            $table->string('sn');
            $table->string('brand');
            $table->string('sn');
            $table->string('location');
            $table->string('address');
            $table->integer('isopen');
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
        Schema::dropIfExists('devices');
    }
}
