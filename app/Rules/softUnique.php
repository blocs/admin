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
        if (!strlen($value)) {
            return;
        }

        // テーブルフォームに対応
        $attribute = explode('.', $attribute);
        $attribute = array_pop($attribute);

        $tableWhere = \DB::table($this->tableName)->where($attribute, $value);

        // 削除データは無視
        $tableWhere = $tableWhere->whereNull('deleted_at');

        // 編集時の自データはOK
        empty(request()->id) || $tableWhere = $tableWhere->where('id', '!=', request()->id);

        if ($tableWhere->exists()) {
            $fail($this->message);
        }
    }
}
