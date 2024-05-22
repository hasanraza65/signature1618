<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class USPhoneNumber implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Regex to validate a US phone number with country code +1 without dashes
        return preg_match('/^\+1\d{10}$/', $value);
    }


    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid USA phone number with country code +1, formatted as +1XXXXXXXXXX (10 digits without dashes).';
    }
}
