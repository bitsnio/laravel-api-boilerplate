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
        Schema::create('room_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_type_id');
            $table->string('room_number');
            $table->string('room_status')->default("available");
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->foreign('room_type_id')->references('id')->on('room_types');
            $table->unique(['property_id', 'room_number']);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_lists');
    }
};
