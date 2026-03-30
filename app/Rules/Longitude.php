<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Longitude implements Rule
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
        // Check if value is numeric
        if (!is_numeric($value)) {
            return false;
        }

        $longitude = (float) $value;

        // Longitude must be between -180 and 180
        return $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a valid longitude between -180 and 180.';
    }
}
