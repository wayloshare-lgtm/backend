<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Carbon\Carbon;

class FutureDate implements Rule
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
        try {
            // Handle empty values
            if (empty($value)) {
                return false;
            }

            $date = Carbon::parse($value);
            $today = Carbon::today();

            // Check if date is in the future (not today or past)
            return $date->isAfter($today);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a future date.';
    }
}
