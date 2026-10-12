<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The insurance tables were originally created outside the migration history, so a fresh
 * install (or a rebuilt test database) could not be created from this repository.
 *
 * Every statement is CREATE TABLE IF NOT EXISTS taken from the live schema, so on a database
 * that already has these tables this migration changes nothing.
 */
class CreateInsuranceTablesIfMissing extends Migration
{
    public function up()
    {
        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `insurance_programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `coverage_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Maximum coverage/benefit amount',
  `premium_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Amount to pay per billing cycle',
  `billing_frequency` enum('Weekly','Monthly','Quarterly','Annually') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Monthly',
  `billing_day` int(11) NOT NULL DEFAULT '1' COMMENT 'Day of week (1-7) or month (1-31) for billing',
  `duration_months` int(11) NOT NULL DEFAULT '12' COMMENT 'Program duration in months',
  `grace_period_days` int(11) NOT NULL DEFAULT '7' COMMENT 'Days allowed for late payment',
  `late_payment_penalty` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Penalty for late payment',
  `penalty_type` enum('Fixed','Percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Fixed',
  `min_age` int(11) NOT NULL DEFAULT '18',
  `max_age` int(11) NOT NULL DEFAULT '70',
  `requirements` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of requirements',
  `benefits` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of benefits',
  `status` enum('Active','Inactive','Suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `start_date` date DEFAULT NULL COMMENT 'Program availability start date',
  `end_date` date DEFAULT NULL COMMENT 'Program availability end date',
  `total_subscribers` int(11) NOT NULL DEFAULT '0',
  `total_premiums_collected` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_premiums_expected` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_premiums_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `terms_and_conditions` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Icon identifier for UI',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Color code for UI',
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_programs_status_index` (`status`),
  KEY `insurance_programs_billing_frequency_index` (`billing_frequency`),
  KEY `insurance_programs_start_date_index` (`start_date`),
  KEY `insurance_programs_end_date_index` (`end_date`),
  KEY `insurance_programs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `insurance_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `insurance_program_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `next_billing_date` date DEFAULT NULL,
  `status` enum('Active','Suspended','Cancelled','Expired','Pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `payment_status` enum('Current','Late','Defaulted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Current',
  `coverage_status` enum('Active','Suspended','Terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `coverage_start_date` date DEFAULT NULL,
  `coverage_end_date` date DEFAULT NULL,
  `premium_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Subscription premium per cycle',
  `total_expected` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total expected premium payments',
  `total_paid` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total amount paid',
  `total_balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Outstanding balance',
  `payments_completed` int(11) NOT NULL DEFAULT '0',
  `payments_pending` int(11) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `beneficiaries` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of beneficiaries',
  `policy_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Generated policy number',
  `prepared` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Payment records generated',
  `suspended_date` date DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `insurance_subscriptions_policy_number_unique` (`policy_number`),
  KEY `insurance_subscriptions_insurance_user_id_index` (`user_id`),
  KEY `insurance_subscriptions_insurance_program_id_index` (`insurance_program_id`),
  KEY `insurance_subscriptions_status_index` (`status`),
  KEY `insurance_subscriptions_payment_status_index` (`payment_status`),
  KEY `insurance_subscriptions_coverage_status_index` (`coverage_status`),
  KEY `insurance_subscriptions_start_date_index` (`start_date`),
  KEY `insurance_subscriptions_end_date_index` (`end_date`),
  KEY `insurance_subscriptions_next_billing_date_index` (`next_billing_date`),
  KEY `insurance_subscriptions_policy_number_index` (`policy_number`),
  KEY `insurance_subscriptions_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `insurance_subscription_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `insurance_subscription_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `insurance_program_id` bigint(20) unsigned NOT NULL,
  `period_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., OCTOBER-2025, 2025-10-27',
  `period_start_date` date NOT NULL,
  `period_end_date` date NOT NULL,
  `year` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `month_number` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `week_number` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_frequency` enum('Weekly','Monthly','Quarterly','Annually') COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Expected payment amount',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Actual amount paid',
  `penalty_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Late payment penalty',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Amount + Penalty',
  `payment_status` enum('Pending','Paid','Partial','Overdue','Waived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `payment_date` date DEFAULT NULL,
  `overdue_date` date DEFAULT NULL COMMENT 'Date when payment became overdue',
  `days_overdue` int(11) NOT NULL DEFAULT '0',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coverage_affected` enum('Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'No' COMMENT 'Did non-payment affect coverage',
  `coverage_suspended_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User who processed payment',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subscription_period` (`insurance_subscription_id`,`period_name`),
  KEY `insurance_subscription_payments_insurance_subscription_id_index` (`insurance_subscription_id`),
  KEY `insurance_subscription_payments_insurance_user_id_index` (`user_id`),
  KEY `insurance_subscription_payments_insurance_program_id_index` (`insurance_program_id`),
  KEY `insurance_subscription_payments_payment_status_index` (`payment_status`),
  KEY `insurance_subscription_payments_due_date_index` (`due_date`),
  KEY `insurance_subscription_payments_payment_date_index` (`payment_date`),
  KEY `insurance_subscription_payments_period_name_index` (`period_name`),
  KEY `insurance_subscription_payments_year_index` (`year`),
  KEY `insurance_subscription_payments_month_number_index` (`month_number`),
  KEY `insurance_subscription_payments_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE IF NOT EXISTS `medical_service_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `insurance_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `service_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `urgency_level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `symptoms_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional_notes` text COLLATE utf8mb4_unicode_ci,
  `preferred_hospital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_doctor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_feedback` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `assigned_hospital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_doctor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `appointment_details` text COLLATE utf8mb4_unicode_ci,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `insurance_coverage` decimal(15,2) DEFAULT NULL,
  `patient_payment` decimal(15,2) DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medical_service_requests_reference_number_unique` (`reference_number`),
  KEY `medical_service_requests_user_id_index` (`user_id`),
  KEY `medical_service_requests_insurance_subscription_id_index` (`insurance_subscription_id`),
  KEY `medical_service_requests_reviewed_by_index` (`reviewed_by`),
  KEY `medical_service_requests_status_index` (`status`),
  KEY `medical_service_requests_service_type_index` (`service_type`),
  KEY `medical_service_requests_urgency_level_index` (`urgency_level`),
  KEY `medical_service_requests_reference_number_index` (`reference_number`),
  KEY `medical_service_requests_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
        );

    }

    public function down()
    {
        // Intentionally empty: these tables hold members' money and are never dropped by a rollback.
    }
}
