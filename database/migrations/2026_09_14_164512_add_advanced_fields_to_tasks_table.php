<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('risk_level')->default('LOW');
            $table->date('start_date')->nullable();
            $table->timestamp('completed_date')->nullable();
            $table->json('dependencies')->nullable(); // array of task IDs
            $table->string('next_action')->nullable();
            $table->text('deliverables')->nullable();
            $table->text('acceptance_criteria')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'risk_level',
                'start_date',
                'completed_date',
                'dependencies',
                'next_action',
                'deliverables',
                'acceptance_criteria'
            ]);
        });
    }
};
