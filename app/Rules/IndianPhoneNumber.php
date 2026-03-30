<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IndianPhoneNumber implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // Convert to string if numeric
        $value = (string) $value;

        // Check if it's exactly 10 digits
        return preg_match('/^\d{10}$/', $value) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a valid Indian phone number (10 digits).';
    }
}
