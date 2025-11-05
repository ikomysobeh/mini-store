<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('donation_beneficiaries', function (Blueprint $table) {
            // Remove unused fields from beneficiary table
            if (Schema::hasColumn('donation_beneficiaries', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('donation_beneficiaries', 'relationship_to_donor')) {
                $table->dropColumn('relationship_to_donor');
            }
        });
    }

    public function down()
    {
        Schema::table('donation_beneficiaries', function (Blueprint $table) {
            // Re-add the columns if rollback is needed
            $table->string('email')->nullable();
            $table->string('relationship_to_donor')->nullable();
        });
    }
};
