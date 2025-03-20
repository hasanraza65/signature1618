<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Passport\Client as OClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact;
use App\Models\User;

class SalesforceController extends Controller
{
    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct()
    {
        $this->client_id = env('SALESFORCE_CLIENT_ID');
        $this->client_secret = env('SALESFORCE_CLIENT_SECRET');
        $this->redirect_uri = env('SALESFORCE_CALLBACK_URL');
    }

    // Step 1: Redirect User to Salesforce Login
    public function redirectToSalesforce()
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'api refresh_token',
        ]);

        return response()->json([
            'url' => "https://login.salesforce.com/services/oauth2/authorize?$query"
        ]);
    }

    // Step 2: Handle Callback & Store Access Token
    public function handleSalesforceCallback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return response()->json(['error' => 'Authorization code not received'], 400);
        }

        $response = Http::asForm()->post('https://login.salesforce.com/services/oauth2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'code' => $code,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to get access token'], 400);
        }

        $salesforceData = $response->json();

        return response()->json([
            'access_token' => $salesforceData['access_token'],
            'refresh_token' => $salesforceData['refresh_token'] ?? null,
            'instance_url' => $salesforceData['instance_url'],
        ]);
    }

    // Step 3: Import Contacts from Salesforce
    public function importContacts(Request $request)
    {
        $accessToken = $request->header('Authorization'); // Get token from frontend
        $instanceUrl = $request->query('instance_url');

        if (!$accessToken || !$instanceUrl) {
            return response()->json(['error' => 'Missing Salesforce credentials'], 400);
        }

        // Fetch contacts from Salesforce
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken"
        ])->get("$instanceUrl/services/data/v52.0/query", [
            'q' => 'SELECT Id, FirstName, LastName, Email, Phone FROM Contact'
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch contacts'], 400);
        }

        $contacts = $response->json()['records'];

        foreach ($contacts as $contact) {
            // Check if contact exists in Laravel
            $existingUser = User::where('email', $contact['Email'])->first();

            if (!$existingUser) {
                $user = new User();
                $user->email = $contact['Email'];
                $user->name = $contact['FirstName'];
                $user->last_name = $contact['LastName'];
                $user->password = bcrypt(str_random(12)); // Random password
                $user->save();
            }

            // Save Contact
            Contact::updateOrCreate(
                ['contact_user_id' => $user->id, 'user_id' => Auth::id()],
                [
                    'contact_first_name' => $contact['FirstName'],
                    'contact_last_name' => $contact['LastName'],
                    'contact_phone' => $contact['Phone'] ?? null,
                    'email' => $contact['Email']
                ]
            );
        }

        return response()->json(['message' => 'Contacts imported successfully']);
    }
}


