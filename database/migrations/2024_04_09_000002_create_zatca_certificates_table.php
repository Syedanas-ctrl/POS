<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZatcaCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zatca_certificates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_location_id')->unsigned();
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->text('csr')->nullable();//csr
            $table->text('private')->nullable();//csr private pem
            $table->text('csid_certificate')->nullable();//csid certificate
            $table->string('csid_secret', 255)->nullable();//csid secret
            $table->string('csid_request_id', 255)->nullable();//csid request id
            $table->string('csid_production_certificate', 255)->nullable();//csid production certificate
            $table->string('csid_production_secret', 255)->nullable();//csid production secret
            $table->string('csid_production_request_id', 255)->nullable();//csid production request id
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
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
        Schema::dropIfExists('zatca_certificates');
    }
}