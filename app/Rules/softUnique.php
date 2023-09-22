<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class softUnique implements DataAwareRule, ValidationRule
{
    private $tableName;
    private $message;
    private $data;

    public function __construct(string $tableName, string $message)
    {
        $this->tableName = $tableName;
        $this->message = $message;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $tableWhere = \DB::table($this->tableName)->where($attribute, $value);
        if ($tableWhere->whereNull('deleted_at')->exists()) {
            $fail($this->message);
        }
    }
}
