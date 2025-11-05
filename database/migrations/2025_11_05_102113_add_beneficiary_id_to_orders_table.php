<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('beneficiary_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('donation_beneficiaries')
                ->onDelete('set null');

            // Add index for better performance
            $table->index('beneficiary_id');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['beneficiary_id']);
            $table->dropIndex(['beneficiary_id']);
            $table->dropColumn('beneficiary_id');
        });
    }
};
