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
        Schema::create('schedule_disruptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('instructor_log_hour_id')->constrained('instructor_log_hours')->onDelete('cascade');
            $table->string('reason');
            $table->enum('status', ['pending', 'voting', 'executed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('response_deadline');
            
            // Final decision fields (hasil voting)
            $table->enum('final_decision', ['cancel', 'reschedule', 'replace_instructor'])->nullable();
            $table->date('final_new_date')->nullable();
            $table->time('final_new_start_time')->nullable();
            $table->time('final_new_end_time')->nullable();
            $table->foreignId('final_replacement_instructor_id')->nullable()->constrained('instructors')->onDelete('set null');
            
            // Voting statistics
            $table->integer('total_students')->default(0);
            $table->integer('responses_count')->default(0);
            $table->json('vote_distribution')->nullable(); // {"cancel": 5, "reschedule": 3, "replace": 2}
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'response_deadline']);
            $table->index('schedule_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_disruptions');
    }
};
