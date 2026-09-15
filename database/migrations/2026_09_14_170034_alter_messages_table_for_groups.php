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
        Schema::table('messages', function (Blueprint $table) {
            // Make receiver_id nullable for group messages
            $table->foreignId('receiver_id')->nullable()->change();
            
            // Add project_id for group messages
            $table->foreignId('project_id')->nullable()->after('receiver_id')->constrained('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            // Reverting receiver_id to non-nullable might fail if there are nulls, but for rollback we try.
            $table->foreignId('receiver_id')->nullable(false)->change();
        });
    }
};
