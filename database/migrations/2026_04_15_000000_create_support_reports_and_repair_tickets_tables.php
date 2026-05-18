<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report_type', 64);
            $table->string('title', 150);
            $table->text('description');
            $table->longText('attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('repair_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->unique()->constrained('support_reports')->cascadeOnDelete();
            $table->string('status', 32)->default('open');
            $table->string('priority', 16)->default('medium');
            $table->text('internal_notes')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_tickets');
        Schema::dropIfExists('support_reports');
    }
};
