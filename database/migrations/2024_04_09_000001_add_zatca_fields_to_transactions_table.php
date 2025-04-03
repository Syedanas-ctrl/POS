<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZatcaFieldsToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('zatca_xml')->nullable()->after('document');
            $table->string('zatca_qr_code', 1000)->nullable()->after('zatca_xml');
            $table->string('zatca_uuid', 255)->nullable()->after('zatca_qr_code');
            $table->string('zatca_hash', 255)->nullable()->after('zatca_uuid');
            $table->string('zatca_status', 255)->nullable()->after('zatca_hash');
            $table->string('zatca_error', 255)->nullable()->after('zatca_status');
            $table->string('zatca_warning', 255)->nullable()->after('zatca_error');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('zatca_xml');
            $table->dropColumn('zatca_qr_code');
            $table->dropColumn('zatca_uuid');
            $table->dropColumn('zatca_hash');
            $table->dropColumn('zatca_status');
            $table->dropColumn('zatca_error');
            $table->dropColumn('zatca_warning');
        });
    }
}