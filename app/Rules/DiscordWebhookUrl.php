<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DiscordWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^https:\/\/discord\.com\/api\/webhooks\/\d+\/.+$/', $value)) {
            $fail('The :attribute must be a valid Discord webhook URL.');
        }
    }
}
