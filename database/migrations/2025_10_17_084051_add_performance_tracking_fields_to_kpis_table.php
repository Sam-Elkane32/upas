<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('k_p_i_s', function (Blueprint $table) {
            // Add performance tracking fields
            $table->decimal('accomplishment_q1', 8, 2)->default(0)->after('target_total');
            $table->decimal('accomplishment_q2', 8, 2)->default(0)->after('accomplishment_q1');
            $table->decimal('accomplishment_q3', 8, 2)->default(0)->after('accomplishment_q2');
            $table->decimal('accomplishment_q4', 8, 2)->default(0)->after('accomplishment_q3');
            $table->decimal('accomplishment_total', 8, 2)->default(0)->after('accomplishment_q4');
            $table->decimal('variance', 8, 2)->default(0)->after('accomplishment_total');
            $table->decimal('rate_of_accomplishment', 5, 2)->default(0)->after('variance');
            $table->string('descriptive_rating')->nullable()->after('rate_of_accomplishment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('k_p_i_s', function (Blueprint $table) {
            $table->dropColumn([
                'accomplishment_q1',
                'accomplishment_q2', 
                'accomplishment_q3',
                'accomplishment_q4',
                'accomplishment_total',
                'variance',
                'rate_of_accomplishment',
                'descriptive_rating'
            ]);
        });
    }
};