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
        Schema::create('student_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_disruption_id')->constrained('schedule_disruptions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            
            // Response choice - only one per student per disruption
            $table->enum('choice', ['cancel', 'reschedule', 'replace_instructor']);
            
            // Additional data for reschedule option
            $table->date('preferred_date')->nullable();
            $table->time('preferred_start_time')->nullable();
            $table->time('preferred_end_time')->nullable();
            
            // Additional data for replace instructor option
            $table->foreignId('preferred_replacement_instructor_id')->nullable()->constrained('instructors')->onDelete('set null');
            
            // Optional notes from student
            $table->text('notes')->nullable();
            $table->timestamp('responded_at');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Ensure one response per student per disruption
            $table->unique(['schedule_disruption_id', 'student_id'], 'unique_student_response');
            
            // Indexes
            $table->index(['schedule_disruption_id', 'choice']);
            $table->index('responded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_responses');
    }
};
