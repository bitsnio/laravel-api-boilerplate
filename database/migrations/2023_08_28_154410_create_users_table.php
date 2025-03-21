<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     Schema::create('users', function (Blueprint $table) {
    //         $table->id();
    //         $table->string('name');
    //         $table->string('email')->unique();
    //         $table->timestamp('email_verified_at')->nullable();
    //         $table->string('password');
    //         $table->rememberToken();
    //         $table->timestamps();
    //         $table->softDeletes();
    //     });

    //     Schema::create('password_reset_tokens', function (Blueprint $table) {
    //         $table->string('email')->primary();
    //         $table->string('token');
    //         $table->timestamp('created_at')->nullable();
    //     });

    //     // Schema::create('sessions', function (Blueprint $table) {
    //     //     $table->string('id')->primary();
    //     //     $table->foreignId('user_id')->nullable()->index();
    //     //     $table->string('ip_address', 45)->nullable();
    //     //     $table->text('user_agent')->nullable();
    //     //     $table->longText('payload');
    //     //     $table->integer('last_activity')->index();
    //     // });
    // }

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->integer('company_id');
            // $table->string('main_module_id');
            // $table->integer('role')->default(0);
            $table->integer('created_by')->default(0);
            $table->integer('updated_by')->default(0);
            
            $table->rememberToken();
            // $table->foreign('company_id')->references('id')->on('companies');
            $table->timestamps();
            $table->unique(['email', 'company_id']);
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
