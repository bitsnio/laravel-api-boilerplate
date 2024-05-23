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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('property_id');
            $table->string('expense_type');
            $table->string('title');
            $table->float('expense_amount');
            $table->string('payment_method')->default('Cash Payment');
            $table->string('payment_reference')->nullable();
            $table->date('expense_date');
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->foreign('property_id')->references('id')->on('properties');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
