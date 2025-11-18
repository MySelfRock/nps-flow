<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class DateRangeValidation implements ValidationRule, DataAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Run the validation rule.
     * Validates that ends_at is after starts_at
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If ends_at is not provided, validation passes
        if (empty($value)) {
            return;
        }

        // Get starts_at from the data
        $startsAt = $this->data['starts_at'] ?? null;

        // If starts_at is not provided, validation passes (let required rule handle it)
        if (empty($startsAt)) {
            return;
        }

        try {
            $startDate = Carbon::parse($startsAt);
            $endDate = Carbon::parse($value);

            // Validate that end date is after start date
            if ($endDate->lte($startDate)) {
                $fail('A data de término deve ser posterior à data de início.');
            }
        } catch (\Exception $e) {
            $fail('Data inválida fornecida.');
        }
    }
}
