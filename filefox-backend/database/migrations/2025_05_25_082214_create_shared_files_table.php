<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSharedFilesTable extends Migration
{
    public function up()
    {
        Schema::create('shared_files', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('item_type', ['document', 'folder']); // Polymorphic type
            $table->unsignedInteger('item_id'); // Points to either documents or folders
            $table->unsignedInteger('shared_with'); // recipient
            $table->unsignedInteger('shared_by');   // owner/sharer
            $table->enum('permission', ['read', 'edit'])->default('read');
            $table->timestamps();

            // Foreign keys for users only
            $table->foreign('shared_with')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shared_by')->references('id')->on('users')->onDelete('cascade');

            // Prevent duplicate sharing for same item + recipient
            $table->unique(['item_type', 'item_id', 'shared_with']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shared_files');
    }
}



