<?php

namespace App\Imports;

use App\Models\Contact;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Auth;

class ContactsImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Check if the user already exists by email
        if($row[2]  != 'Email address'){
            $existingUser = User::where('email', $row[2])->first();

            if ($existingUser) {
                $contact_user_id = $existingUser->id;
            } else {
                // Create a new user
                $user = new User();
                $user->email = $row[2]; // Email address
                $user->name = $row[0] . ' ' . $row[1]; // First name + Last name
                $user->password = bcrypt(Str::random(12)); // Generate random password
                $user->contact_type = 1; // Assuming contact type 1 for non-registered users
                $user->save();
                $contact_user_id = $user->id;
            }

            $user_id = Auth::user()->id;
            
            // Create or update contact data for the user
            $contact = Contact::updateOrCreate(
                ['contact_user_id' => $contact_user_id],
                [
                    'user_id' => $user_id, // Assuming this helper function retrieves user ID
                    'contact_user_id' => $contact_user_id,
                    'job_title' => $row[5], // Job title
                    'company_name' => $row[4], // Company
                    'contact_first_name' => $row[0], // First name
                    'contact_last_name' => $row[1], // Last name
                    'contact_phone' => $row[3], // Phone number
                ]
            );

        

            return $contact;

        }
    
    }

    public function headingRow(): int
    {
        return 2; // Skip the first row (headers) and start reading data from the second row
    }
}
