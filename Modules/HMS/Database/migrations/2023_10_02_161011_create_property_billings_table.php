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
        Schema::create('property_billings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('check_in_id');
            $table->integer('assigned_additional_service_id')->default(0);
            $table->string('item_name');
            $table->float('selling_price');
            $table->float('quantity');
            $table->integer('days');
            $table->string('uom');
            $table->float('total_amount');
            $table->boolean('payment_status')->default(0);
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->foreign('property_id')->references('id')->on('properties');
            $table->foreign('check_in_id')->references('id')->on('check_ins');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_billings');
    }
};
