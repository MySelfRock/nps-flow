<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check minimum length
        if (strlen($value) < 8) {
            $fail('A senha deve ter no mínimo 8 caracteres.');
            return;
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $fail('A senha deve conter pelo menos uma letra maiúscula.');
            return;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $fail('A senha deve conter pelo menos uma letra minúscula.');
            return;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $value)) {
            $fail('A senha deve conter pelo menos um número.');
            return;
        }

        // Check for at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('A senha deve conter pelo menos um caractere especial (!@#$%^&*...).');
            return;
        }
    }
}
