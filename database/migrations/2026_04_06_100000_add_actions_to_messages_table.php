<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Reply threading
            $table->foreignId('reply_to_id')
                  ->nullable()
                  ->after('is_read')
                  ->constrained('messages')
                  ->nullOnDelete();

            // Edit tracking
            $table->timestamp('edited_at')->nullable()->after('reply_to_id');

            // Pin flag
            $table->boolean('is_pinned')->default(false)->after('edited_at');

            // Soft-delete (for delete action)
            $table->softDeletes();

            $table->index('reply_to_id');
            $table->index(['conversation_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropIndex(['reply_to_id']);
            $table->dropIndex(['conversation_id', 'is_pinned']);
            $table->dropColumn(['reply_to_id', 'edited_at', 'is_pinned', 'deleted_at']);
        });
    }
};
