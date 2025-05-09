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
        Schema::create('business_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->string('name', 256);
            $table->string('business_category', 256);
            $table->string('egs_serial_number', 256);

            $table->string('registered_address', 256)->nullable(); //short address on national card
            $table->string('street', 256)->nullable();
            $table->string('building_number', 256)->nullable();
            $table->string('plot_number', 256)->nullable();
            $table->string('sub_division_name', 256)->nullable();
            $table->text('landmark')->nullable();
            $table->string('country', 100);
            $table->string('state', 100)->nullable();
            $table->string('city', 100);
            $table->char('zip_code', 7);

            $table->string('mobile')->nullable();
            $table->string('alternate_number')->nullable();
            $table->string('email')->nullable();
            $table->softDeletes();
            $table->timestamps();

            //Indexing
            $table->index('business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_locations');
    }
};
