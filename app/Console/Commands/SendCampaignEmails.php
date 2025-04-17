<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Subscription;
use App\Models\AIActivity;
use App\Models\UserRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class SendCampaignEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-campaign-emails';

    protected $description = 'Send scheduled campaign emails';

    /**
     * The console command description.
     *
     * @var string
     */
   

    /**
     * Execute the console command.
     */
    private $apiKey = "AIzaSyDHsWIji8M9qjvE6UMcWI2UKMMKcyjvgDQ";

    public function handle()
        {
            
           // Log::info('daily campaign 1');
            
            // Get all active trial users
            $users = User::whereHas('subscriptionDetail', function($query) {
            $query->where('plan_id', 1)
                  ->where('status', 1)
                  ->where('expiry_date', '>', now());
        })
        ->with([
            'subscriptionDetail',
            'aiActivities' => function($query) {
                $query->latest();
            }
        ])
        ->orderBy('id','desc')
        ->limit(15)
        ->get();

        foreach ($users as $user) {
            $this->processUserCampaign($user);
        }
    }

    private function processUserCampaign(User $user)
    {
        
      //  Log::info('daily campaign 2');
        
        $trialDay = now()->diffInDays($user->subscriptionDetail->created_at);
        $dossierCount = $this->getDossierCount($user->id);
        $lastActivity = $user->aiActivities->first();

        // Skip if already received today's email
        if ($lastActivity && $lastActivity->created_at->isToday()) {
            Log::info('received today');
            return;
        }

        // Determine email type based on day and activity
        $emailType = $this->determineEmailType($trialDay, $dossierCount, $user->subscriptionDetail);
        
        Log::info('email type: '.$emailType);

        if ($emailType) {
            $this->sendCampaignEmail($user, $emailType, $trialDay, $dossierCount);
        }
    }

    private function determineEmailType($trialDay, $dossierCount, $subscription)
    {
        
       // Log::info('daily campaign 3 for user: '.$subscription->user_id.' plan id: '.$subscription->plan_id.' trial day: '.$trialDay);
        
        if ($subscription->plan_id != 1) {
            return null;
        }

        switch ($trialDay) {
            case 0:
                return null;
                //return 'DAY_0_WELCOME';
            case 2:
                return $dossierCount === 0 ? 'DAY_2_NO_DOSSIER' : 'DAY_2_FIRST_DOSSIER';
            case 4:
                if ($dossierCount === 0) return 'DAY_4_NO_DOSSIER';
                if ($dossierCount === 1) return 'DAY_4_FIRST_DOSSIER';
                return 'DAY_4_MULTIPLE_DOSSIER';
            case 6:
                if ($dossierCount === 0) return 'DAY_6_NO_DOSSIER';
                if ($dossierCount === 1) return 'DAY_6_FIRST_DOSSIER';
                return 'DAY_6_MULTIPLE_DOSSIER';
            case 9:
                return 'DAY_9_TRIAL_COUNTDOWN';
            case 12:
                return $dossierCount === 0 ? 'DAY_12_NO_DOSSIER' : 'DAY_12_HAS_DOSSIER';
            case 14:
                return 'DAY_14_FINAL_REMINDER';
            case 16:
                return $subscription->expiry_date->isPast() ? 'DAY_16_REACTIVATION' : null;
            default:
                return null;
        }
    }

    private function getDossierCount($userId)
    {
       
       // Log::info('daily campaign 4');
        
        return UserRequest::where('user_id', $userId)->count();
    }

    private function sendCampaignEmail(User $user, $emailType, $trialDay, $dossierCount)
    {
        
        Log::info('campaign mail to: '.$user->email);
        
        $prompt = $this->generatePrompt($user, $emailType, $trialDay, $dossierCount);
        $aiResponse = $this->getGeminiResponse($prompt);

        if ($aiResponse) {
            $this->storeActivity($user->id, $emailType, $aiResponse['subject'], $aiResponse['message']);
            $this->sendEmail($user->email, $aiResponse['subject'], $aiResponse['message']);
        }
    }

    private function generatePrompt($user, $emailType, $trialDay, $dossierCount)
    {
        
        //Log::info('daily campaign 5');
        
        $features_list = 'just in case if you want to highlight any feature or module in email content so here are my application available n functional features list:
            -User can create document/dosier request by uploading PDF file and drag n drop elements.
            - Request will be saved automatically in backend
            - user can keep drafts for future use if dont want to send request for now,
            - user can make requests duplicates
            - user can subscripe to plan from our available 3 plans
            - user can make team upto 5 in enterprise plan, so multiple users upto 5 can use same subscription
            - user can send emails as their own company name
            - users can set branding colors of sign page
            - users can add approvers, who will approve the document first then signer can sign
            - users can manage contacts, so they can send them request to sign or approve
            - elements fields like checkboxes, radio buttons, text inputs, static texts, initials, signatures, mentions are available to be dragged n dropped on document while creating new sign request
            - email n sms otp verification available to keep requests more secured';
        
        $footer_content = '
            - receiver name is '.$user->name.', you can use if you need
            -footer will have these line be line: 
            Best regards,
            Patrick  (make it bold)
            Sales Team
            Signature1618 (make it bold)
            Advanced, Legally-Binding E-Signature Solution
            
            🌐 www.signature1618.com
            ✉️ patrick@signature1618.com
            
            🚀 Start your 14-day free trial: Click here  (hyperlink on click here: https://www.signature1618.app/signup/)
            add 1 line gap before image plz, so img will not collapse with content, keep image at the end
            at the end after footer content put this image at footer: https://signature1618.app/backend_code/public/email-footer.png (width will be 415px and height auto)';
        
        $templates = [
            'DAY_0_WELCOME' => "Generate a welcome email as JSON with 'subject' and 'message' keys. 
            SUBJECT: Welcome to Signature1618 — Your 14-Day Trial Starts Now
            MESSAGE: Should include:
            - Friendly greeting using name {$user->name}
             -kindly regenerate my email subject
             -no bullet points
            - Explain the 14-day trial benefits
            - Clear CTA to send first dossier
            - Hyperlink 'Click here' to https://signature1618.app/
            - Simple HTML formatting
            Example:
            {
                \"subject\": \"Welcome to Signature1618 — Your 14-Day Trial Starts Now\",
                \"message\": \"Hi [Name],<br><br>We're excited to have you...\"
            }",
            
            'DAY_2_NO_DOSSIER' => "Generate an activation email as JSON with 'subject' and 'message' keys.
            SUBJECT: Send your first dossier in 60 seconds
            MESSAGE: Should include:
            - Friendly reminder using name {$user->name}
            - Emphasize how quick and easy it is
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            - Hyperlink 'Click here' to https://signature1618.app/
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Simple HTML formatting",
            
            'DAY_2_FIRST_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Great job! Here's how to go faster
            MESSAGE: Should include:
            - Congratulate {$user->name} on first dossier
            - Introduce templates feature
            - Explain signer order and custom branding
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Hyperlink 'Click here' to https://signature1618.app/
            - Simple HTML formatting",
            
            'DAY_4_NO_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Need help? Let's walk you through it
            MESSAGE: Should include:
            - Empathetic tone using name {$user->name}
            - Offer tutorial/support options
            - Suggest trying with a dummy document
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Hyperlink 'Click here' to https://signature1618.app/
            - Simple HTML formatting",
            
            'DAY_4_FIRST_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Want to automate more of your work?
            MESSAGE: Should include:
            - Acknowledge {$user->name}'s first dossier
            - Introduce bulk sending feature
            - Mention team collaboration options
            - Hyperlink 'Click here' to https://signature1618.app/
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Simple HTML formatting",
            
            'DAY_4_MULTIPLE_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Power user tips to save time
            MESSAGE: Should include:
            - Recognize {$user->name}'s activity
            - Highlight analytics features
            - Explain duplicate requests, auto save requests, easily manageable signers
            - Mention integrations available
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Hyperlink 'Click here' to https://signature1618.app/
            - Simple HTML formatting",
            
            'DAY_6_NO_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Here's how others are using Signature1618
            MESSAGE: Should include:
            - Testimonials from similar users
            - Case studies showing benefits
            - Success metrics examples
            - Hyperlink 'Click here' to https://signature1618.app/
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Simple HTML formatting",
            
            'DAY_6_FIRST_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: You're off to a great start — see what's next
            MESSAGE: Should include:
            - Positive reinforcement for {$user->name}
            - Personalized use case suggestions
            - Habit-building encouragement
            - Hyperlink 'Click here' to https://signature1618.app/
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Simple HTML formatting",
            
            'DAY_6_MULTIPLE_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Your trial is flying — make the most of it
            MESSAGE: Should include:
            - ROI calculation examples
            - Time-saving statistics
            - Conversion encouragement
            - Hyperlink 'Click here' to https://signature1618.app/
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Simple HTML formatting",
            
            'DAY_9_TRIAL_COUNTDOWN' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: 5 days left in your free trial
            MESSAGE: Should include:
            - Countdown urgency for {$user->name}
            - Highlight what's at stake

            - Hyperlink 'Click here' to https://signature1618.app/
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Simple HTML formatting",
            
            'DAY_12_NO_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: It's not too late — try sending a dossier today
            MESSAGE: Should include:
            - Last chance encouragement
            - 'Test it before it's over' angle
            - Hyperlink 'Click here' to https://signature1618.app/
            - Simple HTML formatting
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject",
            
            'DAY_12_HAS_DOSSIER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: You've come this far — don't lose momentum
            MESSAGE: Should include:
            - Progress acknowledgment
            - Upgrade benefits summary
            - Active usage statistics
            - Hyperlink 'Click here' to https://signature1618.app/
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Simple HTML formatting",
            
            'DAY_14_FINAL_REMINDER' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Your trial ends today — Upgrade to keep going
            MESSAGE: Should include:
            - Final urgency for {$user->name}
            - Features they'll lose access to
            - Possible discount/incentive
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
             -kindly regenerate my email subject
            - Hyperlink 'Click here' to https://signature1618.app/
            - Simple HTML formatting",
            
            'DAY_16_REACTIVATION' => "Generate an email as JSON with 'subject' and 'message' keys.
            SUBJECT: Missed it? Here's 3 more days to try Signature1618
            MESSAGE: Should include:
            - Special extension offer
            - Limited-time opportunity
            -explain things so at least we have some content in emails atleast 6-7 lines paragraph
            -no any bullet points, just simple explain 
            -kindly regenerate my email subject
            - Success stories
            - Hyperlink 'Click here' to https://signature1618.app/
            - Simple HTML formatting"
        ];
        
        $template = $templates[$emailType] ?? $templates['DAY_0_WELCOME'];
        
        $template .= "\n- Features list (if you want to show any feature inside message content): " . $features_list;
    
        // Add footer instruction to the template
        $template .= "\n- Footer: " . $footer_content;
        
        return $template;
    
        //return $templates[$emailType] ?? $templates['DAY_0_WELCOME'];
    }

    private function getGeminiResponse($prompt)
    {
       // Log::info('daily campaign 6');
        
        try {
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$this->apiKey}", [
                'contents' => [[
                    'parts' => [['text' => $prompt]]
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ]);

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            $json = json_decode($text, true);

            return [
                'subject' => $json['subject'] ?? 'Action needed',
                'message' => $json['message'] ?? 'Please check your account'
            ];

        } catch (\Exception $e) {
            Log::error('Gemini API error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function storeActivity($userId, $type, $subject, $content)
    {
        
       // Log::info('daily campaign 7');
        
        AIActivity::create([
            'user_id' => $userId,
            'activity_type' => $type,
            'mail_subject' => $subject,
            'mail_content' => $content
        ]);
    }

    private function sendEmail($to, $subject, $htmlContent)
    {
       // Log::info('daily campaign 8');
        
        
        try {
            Mail::send([], [], function ($message) use ($to, $subject, $htmlContent) {
                $message->from('patrick@signature1618.com', 'Signature1618 - Patrick')
                       ->to($to)
                       ->subject($subject)
                       ->html($htmlContent);
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
