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
        Schema::create('sub_modules', function (Blueprint $table) {
            $table->id();
            $table->integer('menu_order');
            $table->unsignedBigInteger('main_module_id');
            $table->string('title');
            $table->string('route');
            $table->string('slug');
            $table->string('icon');
            $table->integer('created_by');
            $table->integer('updated_by')->default(0);
            $table->integer('deleted_by')->default(0)->default(0);
            $table->foreign('main_module_id')->references('id')->on('main_modules');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_modules');
    }
};
