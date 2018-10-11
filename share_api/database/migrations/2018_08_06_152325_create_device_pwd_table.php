<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicePwdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('device_pwd', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('device_id');
            $table->integer('sn');
            $table->string('password', 100)->default(null);
            $table->string('orderid', 100)->default(null);
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
        Schema::dropIfExists('device_pwd');
    }
}
