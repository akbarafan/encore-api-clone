<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade'); // bisa family atau instructor
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade'); // untuk family yang kirim atas nama student
            $table->enum('sender_type', ['student', 'instructor']); // tipe pengirim
            $table->text('message'); // isi pesan
            $table->foreignId('reply_to')->nullable()->constrained('message_classes')->onDelete('set null'); // untuk reply
            $table->json('attachments')->nullable(); // file attachment (JSON array)
            $table->boolean('is_read')->default(false); // status baca
            $table->timestamp('read_at')->nullable(); // kapan dibaca
            $table->boolean('is_pinned')->default(false); // pin message penting
            $table->boolean('is_announcement')->default(false); // pengumuman dari instructor
            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index(['class_id', 'created_at']);
            $table->index(['user_id', 'is_read']);
            $table->index(['sender_type', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_classes');
    }
};
