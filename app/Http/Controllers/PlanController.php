<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\PlanFeature;

class PlanController extends Controller
{
    public function index(){

        $data = Plan::with('planFeatures')->get();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function store(Request $request){

        $data = new Plan();
        $data->plan_name = $request->plan_name;
        $data->per_month_charges = $request->per_month_charges; 
        $data->per_year_charges = $request->per_year_charges;
        $data->save();
    
        // Adding features
        $feature = new PlanFeature();
        $feature->plan_id = $data->id;
        $feature->docs_limit = $request->docs_limit;
        $feature->frictionless_signing_workflows = $request->frictionless_signing_workflows;
        $feature->diverse_signature_options = $request->diverse_signature_options;
        $feature->responsive_mobile_signing = $request->responsive_mobile_signing;
        $feature->team_collaboration = $request->team_collaboration;
        $feature->in_person_signing = $request->in_person_signing;
        $feature->send_manual_reminders = $request->send_manual_reminders;
        $feature->automatic_reminders_system = $request->automatic_reminders_system;
        $feature->customizable_expiration_settings = $request->customizable_expiration_settings;
        $feature->real_time_progress_tracking = $request->real_time_progress_tracking;
        $feature->single_and_multiple_signature_fields = $request->single_and_multiple_signature_fields;
        $feature->initials = $request->initials;
        $feature->text_fields = $request->text_fields;
        $feature->mentions = $request->mentions;
        $feature->checkboxes = $request->checkboxes;
        $feature->secure_document_attachments = $request->secure_document_attachments;
        $feature->email_access_codes_authentication = $request->email_access_codes_authentication;
        $feature->sms_and_phone_authentication = $request->sms_and_phone_authentication;
        $feature->id_verification_authentication = $request->id_verification_authentication;
        $feature->liveness_verification_authentication = $request->liveness_verification_authentication;
        $feature->eIDAS_certified = $request->eIDAS_certified;
        $feature->GDPR_compliant = $request->GDPR_compliant;
        $feature->user_management_and_organization_inbox = $request->user_management_and_organization_inbox;
        $feature->simplified_contact_synchronization = $request->simplified_contact_synchronization;
        $feature->streamlined_contact_management = $request->streamlined_contact_management;
        $feature->organization_wide_settings = $request->organization_wide_settings;
        $feature->document_management_and_tracking_secure_attachments = $request->document_management_and_tracking_secure_attachments;
        $feature->document_management_and_tracking_in_depth_reporting = $request->document_management_and_tracking_in_depth_reporting;
        $feature->document_management_and_tracking_retention_policies = $request->document_management_and_tracking_retention_policies;
        $feature->advanced_features_generate_secure_links = $request->advanced_features_generate_secure_links;
        $feature->advanced_features_configurable_approval_workflows = $request->advanced_features_configurable_approval_workflows;
        $feature->advanced_features_global_acceptance = $request->advanced_features_global_acceptance;
        $feature->save();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }

    public function update(Request $request, $id){

        $data = Plan::find($id);
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
        $data->plan_name = $request->plan_name;
        $data->per_month_charges = $request->per_month_charges; 
        $data->per_year_charges = $request->per_year_charges;
        $data->update();
    
        // Adding features
        $feature = PlanFeature::where('plan_id',$id)->first();
        if(!$feature){
            $feature = new PlanFeature();
            $feature->plan_id = $id;
        }
        $feature->docs_limit = $request->docs_limit;
        $feature->frictionless_signing_workflows = $request->frictionless_signing_workflows;
        $feature->diverse_signature_options = $request->diverse_signature_options;
        $feature->responsive_mobile_signing = $request->responsive_mobile_signing;
        $feature->team_collaboration = $request->team_collaboration;
        $feature->in_person_signing = $request->in_person_signing;
        $feature->send_manual_reminders = $request->send_manual_reminders;
        $feature->automatic_reminders_system = $request->automatic_reminders_system;
        $feature->customizable_expiration_settings = $request->customizable_expiration_settings;
        $feature->real_time_progress_tracking = $request->real_time_progress_tracking;
        $feature->single_and_multiple_signature_fields = $request->single_and_multiple_signature_fields;
        $feature->initials = $request->initials;
        $feature->text_fields = $request->text_fields;
        $feature->mentions = $request->mentions;
        $feature->checkboxes = $request->checkboxes;
        $feature->secure_document_attachments = $request->secure_document_attachments;
        $feature->email_access_codes_authentication = $request->email_access_codes_authentication;
        $feature->sms_and_phone_authentication = $request->sms_and_phone_authentication;
        $feature->id_verification_authentication = $request->id_verification_authentication;
        $feature->liveness_verification_authentication = $request->liveness_verification_authentication;
        $feature->eIDAS_certified = $request->eIDAS_certified;
        $feature->GDPR_compliant = $request->GDPR_compliant;
        $feature->user_management_and_organization_inbox = $request->user_management_and_organization_inbox;
        $feature->simplified_contact_synchronization = $request->simplified_contact_synchronization;
        $feature->streamlined_contact_management = $request->streamlined_contact_management;
        $feature->organization_wide_settings = $request->organization_wide_settings;
        $feature->document_management_and_tracking_secure_attachments = $request->document_management_and_tracking_secure_attachments;
        $feature->document_management_and_tracking_in_depth_reporting = $request->document_management_and_tracking_in_depth_reporting;
        $feature->document_management_and_tracking_retention_policies = $request->document_management_and_tracking_retention_policies;
        $feature->advanced_features_generate_secure_links = $request->advanced_features_generate_secure_links;
        $feature->advanced_features_configurable_approval_workflows = $request->advanced_features_configurable_approval_workflows;
        $feature->advanced_features_global_acceptance = $request->advanced_features_global_acceptance;

        if(!$feature){
        $feature->save();
        }else{
        $feature->update();
        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function show($id){

        $data = Plan::with('planFeatures')->find($id);
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function destroy($id){

        $data = Plan::find($id);
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $data->delete();
        PlanFeature::where('plan_id',$id)->delete();


        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }
    
}
