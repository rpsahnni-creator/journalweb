<?php

namespace App\Rules;

use App\Support\WordCounter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WordCount implements ValidationRule
{
    public function __construct(
        private readonly int $min,
        private readonly int $max,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $count = WordCounter::count(is_string($value) ? $value : null);

        if ($count < $this->min || $count > $this->max) {
            $fail("The {$attribute} must be between {$this->min} and {$this->max} words (currently {$count}).");
        }
    }
}
