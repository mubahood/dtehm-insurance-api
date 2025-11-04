<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_users', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('sex', ['Male', 'Female']);
            $table->date('date_of_birth')->nullable();
            
            // Contact Information
            $table->string('phone_number_1');
            $table->string('phone_number_2')->nullable();
            $table->string('email')->nullable();
            
            // Location Information
            $table->string('nationality')->default('Uganda');
            $table->string('referral')->nullable()->comment('District'); // Used as district field
            $table->text('home_address')->nullable();
            $table->text('current_address')->nullable();
            $table->string('swimming')->nullable()->comment('Tribe'); // Used as tribe field
            
            // Family Information
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            
            // Children Information (biological children)
            $table->string('transportation')->nullable()->comment('1st Child');
            $table->string('residential_type')->nullable()->comment('2nd Child');
            $table->string('school_pay_account_id')->nullable()->comment('3rd Child');
            $table->string('phd_university_year_graduated')->nullable()->comment('4th Child');
            
            // Sponsor Information
            $table->string('phd_university_name')->nullable()->comment('Sponsor ID No.');
            
            // Profile Photo
            $table->string('avatar')->nullable();
            
            // Status and Metadata
            $table->enum('status', ['0', '1', 'Pending', 'Active', 'Inactive'])->default('1');
            $table->integer('user_id')->nullable()->comment('Linked to main users table');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('phone_number_1');
            $table->index('email');
            $table->index('status');
            $table->index(['first_name', 'last_name']);
            $table->index('user_id');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('insurance_users');
    }
}
