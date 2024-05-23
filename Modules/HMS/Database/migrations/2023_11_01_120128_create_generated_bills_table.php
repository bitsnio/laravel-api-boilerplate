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
        Schema::create('generated_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('check_in_id');
            $table->string('billing_status')->default('bill_generated');
            $table->date('billing_date');
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
        Schema::dropIfExists('generated_bills');
    }
};
