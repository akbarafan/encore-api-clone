<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->enum('material_type', ['pre_class', 'post_class'])->default('pre_class'); // sebelum atau sesudah pembelajaran
            $table->string('title')->nullable(); // judul aktivitas
            $table->text('description')->nullable(); // deskripsi aktivitas
            $table->text('instructions')->nullable(); // instruksi untuk student
            $table->boolean('is_mandatory')->default(false); // apakah wajib dikerjakan
            $table->timestamp('available_from')->nullable(); // kapan mulai tersedia
            $table->timestamp('due_date')->nullable(); // batas waktu (untuk post_class activities)
            $table->boolean('is_active')->default(true); // status aktif
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index(['schedule_id', 'material_type']);
            $table->index(['instructor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
