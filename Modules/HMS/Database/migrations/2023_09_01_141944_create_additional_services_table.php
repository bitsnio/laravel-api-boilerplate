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
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('service_name');
            $table->string('basis_of_application');
            $table->string('frequency');
            $table->float('cost');
            $table->float('selling_price');            
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->unique(['service_name', 'company_id'], 'additional_service_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_services');
    }
};
