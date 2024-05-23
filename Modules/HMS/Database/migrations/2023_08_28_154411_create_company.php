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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_email');
            $table->string('company_phone');
            $table->string('country');
            $table->string('city');
            $table->string('street_address');
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_name', 'company_email'], 'company_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
