<?php

namespace Modules\HMS\App\Rules;

use Illuminate\Validation\Rule;
use Closure;
use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidVisaExpiry implements ValidationRule
{
    protected $date_of_birth;

    public function __construct($date_of_birth)
    {
        $this->date_of_birth = $date_of_birth;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail):void
    {
        $timezone = new DateTimeZone('Asia/Karachi');
        $visaExpiry = DateTime::createFromFormat('Y-m-d', $value, $timezone);
        if ($visaExpiry > $this->date_of_birth) {
            $fail('The attribute must be a valid visa expiry date that is greater than the date of birth.');
        }
        // return $visaExpiry > $this->date_of_birth;
    }

}
