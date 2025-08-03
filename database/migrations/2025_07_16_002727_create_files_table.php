<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name'); // nama file asli
            $table->string('file_name'); // nama file di storage
            $table->string('file_path'); // path lengkap file
            $table->string('mime_type'); // tipe file (pdf, doc, etc)
            $table->bigInteger('file_size'); // ukuran file dalam bytes
            $table->string('file_extension'); // ekstensi file
            $table->enum('file_category', ['material', 'assignment', 'resource', 'other'])->default('material');
            $table->foreignId('uploaded_by')->constrained('instructors')->onDelete('cascade'); // siapa yang upload
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
