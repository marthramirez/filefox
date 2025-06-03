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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id');
            $table->integer('version_number');
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
