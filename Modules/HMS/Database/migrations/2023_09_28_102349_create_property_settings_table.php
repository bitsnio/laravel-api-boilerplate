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
        Schema::create('property_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('key');
            $table->string('value');
            $table->string('value_type');
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0)->default(0);
            $table->timestamps();
            $table->softDeletes();
            // $table->foreign('property_id')->references('id')->on('properties');
            $table->unique(['property_id', 'key','value','value_type'],'Property_Settings_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_settings');
    }
};
