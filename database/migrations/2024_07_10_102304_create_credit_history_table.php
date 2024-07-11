<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_history', function (Blueprint $table) {
            $table->id();
            $table->string('user_cpf', 14)->notNullable();
            $table->integer('instituition_id')->nullable();
            $table->string('modality', 50)->nullable();
            $table->decimal('value_requested', 10, 2)->nullable();
            $table->integer('installments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_history');
    }
};
