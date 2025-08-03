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
        Schema::create('instructor_log_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->onDelete('cascade');
            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->enum('activity_type', ['teaching', 'admin', 'time_off', 'sick'])->default('teaching');
            $table->text('clock_in_notes')->nullable();
            $table->text('clock_out_notes')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');

            // Disruption tracking fields
            $table->boolean('causes_disruption')->default(false);
            $table->enum('disruption_status', ['none', 'pending', 'resolved'])->default('none');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['activity_type', 'date']);
            $table->index(['causes_disruption', 'disruption_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_log_hours');
    }
};
