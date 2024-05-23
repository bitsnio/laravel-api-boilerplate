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
        Schema::create('assigned_additional_services', function (Blueprint $table) {
            $table->id();
            $table->integer('property_id');
            $table->unsignedBigInteger('check_in_id');
            $table->string('service_name');
            $table->string('basis_of_application');
            $table->string('frequency');
            $table->float('cost');
            $table->float('selling_price');
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
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
        Schema::dropIfExists('assigned_additional_services');
    }
};
