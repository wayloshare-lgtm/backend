<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Carbon\Carbon;

class DateOfBirth implements Rule
{
    private int $minimumAge = 18;

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
            $dob = Carbon::parse($value);
            $today = Carbon::today();

            // Check if DOB is not in the future
            if ($dob->isAfter($today)) {
                return false;
            }

            // Check if age is at least 18 years
            // Calculate age by checking if 18 years from DOB is before or equal to today
            $eighteenYearsFromDob = $dob->copy()->addYears($this->minimumAge);
            return $eighteenYearsFromDob->isBefore($today) || $eighteenYearsFromDob->isToday();
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
        return "The :attribute must be a valid date of birth with age at least {$this->minimumAge} years.";
    }
}
