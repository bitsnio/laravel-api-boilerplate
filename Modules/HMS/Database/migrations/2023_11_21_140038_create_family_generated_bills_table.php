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
        Schema::create('family_generated_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('registeration_number');
            $table->string('family_name');
            $table->string('check_in_ids');
            $table->float('total_merged_amount');
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('company_id')->references('id')->on('companies');
            // $table->foreign('registeration_number')->references('registeration_number')->on('check_ins');
            $table->unique(['company_id', 'check_in_ids'],'Family_Bills_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_generated_bills');
    }
};
