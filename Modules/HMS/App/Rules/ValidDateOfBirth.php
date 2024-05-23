<?php

namespace Modules\HMS\App\Rules;

use Modules\HMS\App\Utilities\Helper;
use Closure;
use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDateOfBirth implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail):void
    {
        $timezone = new DateTimeZone('Asia/Karachi');

        $birthdate = DateTime::createFromFormat('Y-m-d', $value, $timezone);
        if ($birthdate <= new DateTime('now', $timezone)) {
            $fail('Attribute must be a valid birthdate that is not greater than today');
        }

    }

}
