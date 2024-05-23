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
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->integer('last_check_in_id')->default(0);
            $table->integer('parent_id')->default(0);
            $table->string('registeration_number');
            $table->string('family_name');
            $table->string('check_in_type');
            $table->string('check_in_status')->default('active');
            $table->string('present_status')->default('continue');
            $table->integer('total_persons');
            $table->string('bound_country');
            $table->string('selected_services')->nullable();
            $table->string('payment_type');
            $table->date('check_in_date');
            $table->time('check_in_time');
            $table->date('expected_check_out_date')->nullable();
            $table->time('expected_check_out_time')->nullable();
            $table->string('booking_notes')->nullable();
            $table->date('check_out_date')->nullable();
            $table->time('check_out_time')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->unique(['registeration_number', 'check_in_status', 'property_id', 'last_check_in_id', 'created_at'],'Check_in_index_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
