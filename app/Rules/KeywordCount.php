<?php

namespace App\Rules;

use App\Support\WordCounter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class KeywordCount implements ValidationRule
{
    public function __construct(
        private readonly int $min,
        private readonly int $max,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $count = count(WordCounter::keywords(is_string($value) ? $value : null));

        if ($count < $this->min || $count > $this->max) {
            $fail("Please provide between {$this->min} and {$this->max} keywords, separated by commas (currently {$count}).");
        }
    }
}
