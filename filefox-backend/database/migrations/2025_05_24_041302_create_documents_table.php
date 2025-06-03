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
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->string('file_type', 50);
            $table->integer('file_size');
            $table->boolean('is_archived')->default(false);
            $table->unsignedInteger('uploaded_by');
            $table->unsignedInteger('folder_id')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('folder_id')->nullable()->references('id')->on('folders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
