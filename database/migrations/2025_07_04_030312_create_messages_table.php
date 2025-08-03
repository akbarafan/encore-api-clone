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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('from_user_id');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('to_user_id');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->string('method')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('to_class_id')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
