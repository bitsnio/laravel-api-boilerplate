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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('category');
            $table->string('property_name');
            $table->string('property_type');
            $table->string('property_email');
            $table->string('property_phone');
            $table->string('city');
            $table->string('postal_code');
            $table->string('street_address');
            $table->string('description')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->unique(['company_id', 'property_email'], 'property_key');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
