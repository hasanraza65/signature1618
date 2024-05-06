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
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->integer('plan_id')->nullable();
            $table->integer('docs_limit')->default(20);
            $table->boolean('frictionless_signing_workflows')->default(false);
            $table->boolean('diverse_signature_options')->default(false);
            $table->boolean('responsive_mobile_signing')->default(false);
            $table->boolean('team_collaboration')->default(false);
            $table->boolean('in_person_signing')->default(false);
            $table->boolean('send_manual_reminders')->default(false);
            $table->boolean('automatic_reminders_system')->default(false);
            $table->boolean('customizable_expiration_settings')->default(false);
            $table->boolean('real_time_progress_tracking')->default(false);
            $table->boolean('single_and_multiple_signature_fields')->default(false);
            $table->boolean('initials')->default(false);
            $table->boolean('text_fields')->default(false);
            $table->boolean('mentions')->default(false);
            $table->boolean('checkboxes')->default(false);
            $table->boolean('secure_document_attachments')->default(false);
            $table->boolean('email_access_codes_authentication')->default(false);
            $table->boolean('sms_and_phone_authentication')->default(false);
            $table->boolean('id_verification_authentication')->default(false);
            $table->boolean('liveness_verification_authentication')->default(false);
            $table->boolean('eIDAS_certified')->default(false);
            $table->boolean('GDPR_compliant')->default(false);
            $table->boolean('user_management_and_organization_inbox')->default(false);
            $table->boolean('simplified_contact_synchronization')->default(false);
            $table->boolean('streamlined_contact_management')->default(false);
            $table->boolean('organization_wide_settings')->default(false);
            $table->boolean('document_management_and_tracking_secure_attachments')->default(false);
            $table->boolean('document_management_and_tracking_in_depth_reporting')->default(false);
            $table->boolean('document_management_and_tracking_retention_policies')->default(false);
            $table->boolean('advanced_features_generate_secure_links')->default(false);
            $table->boolean('advanced_features_configurable_approval_workflows')->default(false);
            $table->boolean('advanced_features_global_acceptance')->default(false);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
