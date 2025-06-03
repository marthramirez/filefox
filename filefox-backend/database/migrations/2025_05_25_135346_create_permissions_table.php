<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTable extends Migration
{
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('permission', ['viewer', 'editor']);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('granted_by');
            
            $table->unsignedInteger('permissionable_id');
            $table->string('permissionable_type');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('restrict');
            
            $table->index(['permissionable_id', 'permissionable_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('permissions');
    }
}
