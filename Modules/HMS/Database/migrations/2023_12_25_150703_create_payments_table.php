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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('receipt_id');
            $table->float('total_amount');
            $table->float('paid_amount');
            $table->string('payment_status')->default('unpaid');
            $table->date('payment_date');
            $table->string('payment_method')->default('Cash Payment');
            $table->string('payment_reference')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->foreign('property_id')->references('id')->on('properties');
            $table->foreign('receipt_id')->references('id')->on('receipts');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
