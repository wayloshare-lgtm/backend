<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Latitude implements Rule
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

        $latitude = (float) $value;

        // Latitude must be between -90 and 90
        return $latitude >= -90 && $latitude <= 90;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a valid latitude between -90 and 90.';
    }
}
