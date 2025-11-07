<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class softUnique implements ValidationRule
{
    private string $tableName;

    private string $message;

    public function __construct(string $tableName, string $message)
    {
        $this->tableName = $tableName;
        $this->message = $message;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if ($this->isValueEmpty($value)) {
            return;
        }

        // テーブルフォームに対応するため、ドット記法から最終的な属性名を特定
        $attributeName = $this->extractAttributeName($attribute);

        $query = $this->initializeSoftUniqueQuery($attributeName, $value);

        // 削除データは無視し、ソフトデリート済みのレコードを除外
        $query = $this->excludeSoftDeletedRecords($query);

        // 編集時の自データはOKとし、現在編集中のレコードを除外
        $query = $this->excludeCurrentRecord($query);

        if ($query->exists()) {
            $fail($this->message);
        }
    }

    private function isValueEmpty(mixed $value): bool
    {
        return strlen((string) $value) === 0;
    }

    private function extractAttributeName(string $attribute): string
    {
        $segments = explode('.', $attribute);
        $resolvedAttribute = array_pop($segments);

        return $resolvedAttribute ?? $attribute;
    }

    private function initializeSoftUniqueQuery(string $attributeName, mixed $value): Builder
    {
        return DB::table($this->tableName)->where($attributeName, $value);
    }

    private function excludeSoftDeletedRecords(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    private function excludeCurrentRecord(Builder $query): Builder
    {
        $currentId = $this->resolveCurrentRecordId();

        if ($currentId === null) {
            return $query;
        }

        return $query->where('id', '!=', $currentId);
    }

    private function resolveCurrentRecordId(): ?string
    {
        $request = request();

        if (! $request) {
            return null;
        }

        $requestId = $request->id;

        return $requestId !== null && $requestId !== '' ? (string) $requestId : null;
    }
}
