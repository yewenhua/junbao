<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePricetplTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pricetpl', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('uid');
            $table->string('ptype');
            $table->integer('signal_id');
            $table->integer('price');
            $table->string('name', 50)->default(null)->comment('价格规则名称');
            $table->string('description', 200)->default(null)->comment('价格规则描述');
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
        Schema::dropIfExists('pricetpl');
    }
}
