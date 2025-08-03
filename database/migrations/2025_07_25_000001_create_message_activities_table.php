<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade'); // hanya instructor yang bisa kirim
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade'); // pesan per class
            $table->string('title')->nullable(); // judul kegiatan/moment
            $table->text('message'); // isi pesan tentang kegiatan/moment hari itu
            $table->json('attachments')->nullable(); // file attachment (JSON array)
            $table->date('activity_date'); // tanggal kegiatan
            $table->boolean('is_pinned')->default(false); // pin message penting
            $table->boolean('is_active')->default(true); // status aktif
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index(['class_id', 'activity_date']);
            $table->index(['instructor_id', 'is_active']);
            $table->index(['activity_date', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_activities');
    }
};
