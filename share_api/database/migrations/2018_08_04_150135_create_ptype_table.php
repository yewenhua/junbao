<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePtypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ptype', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->default(null)->comment('分类名称');
            $table->integer('isopen')->default(0);
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
        Schema::dropIfExists('ptype');
    }
}
