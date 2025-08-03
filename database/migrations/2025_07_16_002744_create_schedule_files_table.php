<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->string('title')->nullable(); // judul materi untuk schedule ini
            $table->text('description')->nullable(); // deskripsi materi
            $table->integer('order')->default(0); // urutan materi
            $table->boolean('is_required')->default(false); // apakah wajib didownload
            $table->timestamp('available_from')->nullable(); // kapan mulai bisa diakses
            $table->timestamp('available_until')->nullable(); // sampai kapan bisa diakses
            $table->timestamps();

            // Prevent duplicate file in same schedule
            $table->unique(['schedule_id', 'file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_files');
    }
};
