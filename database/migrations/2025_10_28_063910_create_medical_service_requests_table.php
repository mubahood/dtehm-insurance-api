<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicalServiceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medical_service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('insurance_subscription_id')->nullable();
            
            // Service Details
            $table->string('service_type'); // consultation, emergency, lab_test, prescription, surgery, etc.
            $table->string('service_category')->nullable(); // general, specialist, dental, optical, etc.
            $table->string('urgency_level')->default('normal'); // emergency, urgent, normal
            $table->text('symptoms_description');
            $table->text('additional_notes')->nullable();
            
            // Preferred Details
            $table->string('preferred_hospital')->nullable();
            $table->string('preferred_doctor')->nullable();
            $table->date('preferred_date')->nullable();
            $table->time('preferred_time')->nullable();
            
            // Contact Information
            $table->string('contact_phone');
            $table->string('contact_email')->nullable();
            $table->text('contact_address')->nullable();
            
            // Request Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('admin_feedback')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            
            // Hospital Assignment
            $table->string('assigned_hospital')->nullable();
            $table->string('assigned_doctor')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->text('appointment_details')->nullable();
            
            // Cost & Coverage
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('insurance_coverage', 15, 2)->nullable();
            $table->decimal('patient_payment', 15, 2)->nullable();
            
            // Attachments
            $table->json('attachments')->nullable(); // medical reports, prescriptions, etc.
            
            // Meta
            $table->string('reference_number')->unique();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('insurance_subscription_id');
            $table->index('reviewed_by');
            $table->index('status');
            $table->index('service_type');
            $table->index('urgency_level');
            $table->index('reference_number');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medical_service_requests');
    }
}
