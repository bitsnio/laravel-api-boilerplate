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
        Schema::create('checked_in_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('check_in_id');
            $table->string('guest_name');
            $table->date('date_of_birth');
            $table->string('room_number');
            $table->string('customer_type')->nullable();
            $table->string('cnic_passport_number');
            $table->date('visa_expiry')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_province')->nullable();
            $table->string('customer_postal_code')->nullable();
            $table->string('customer_home_address')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('property_id')->references('id')->on('properties');
            $table->foreign('check_in_id')->references('id')->on('check_ins');
            $table->unique(['cnic_passport_number', 'check_in_id'], 'members_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checked_in_members');
    }
};
