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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons');
            $table->foreignId('class_category_id')->constrained('class_categories');
            $table->foreignId('class_location_id')->constrained('class_locations');
            $table->foreignId('class_time_id')->constrained('class_times');
            $table->foreignId('class_type_id')->constrained('class_types');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('instructor_id')->constrained('instructors');
            $table->decimal('cost', 10, 2);
            $table->dateTime('scheduled_at')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
