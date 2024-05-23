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
        Schema::create('property_service_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_service_id');
            $table->string('title');
            $table->string('from');
            $table->string('to');
            $table->string('charge_compare_with');
            $table->float('charge_percentage');
            $table->string('apply_on');
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->foreign('property_service_id')->references('id')->on('property_services');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_service_rules');
    }
};
