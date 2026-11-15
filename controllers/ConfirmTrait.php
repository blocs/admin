<?php

namespace Blocs\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * 確認画面の入力値をセッションで受け渡す。
 * 入力値は操作の種類と対象IDに紐付けて保存し、別の操作や別レコードの確認内容を流用できないようにする。
 */
trait ConfirmTrait
{
    /**
     * 確認画面用に、入力値と「どの操作・どのレコードの確認か」を次のリクエストまで保存する。
     * 実行時にもう一度検証するため、検証（フィルタ適用）前の入力値も渡しておく。
     */
    protected function saveConfirmToSession(string $action, mixed $id = null, ?array $rawInput = null): void
    {
        session()->flash($this->viewPrefix.'.confirm', $this->request->all());
        session()->flash($this->viewPrefix.'.confirmContext', [
            'action' => $action,
            'id' => $this->normalizeConfirmId($id),
            'input' => $rawInput,
        ]);
    }

    /**
     * 同じ操作・同じレコードの確認内容がセッションに残っているかを判定する。
     */
    protected function hasConfirmInSession(string $action, mixed $id = null): bool
    {
        if (! is_array(session($this->viewPrefix.'.confirm'))) {
            return false;
        }

        $context = session($this->viewPrefix.'.confirmContext');

        return is_array($context)
            && ($context['action'] ?? null) === $action
            && ($context['id'] ?? null) === $this->normalizeConfirmId($id);
    }

    /**
     * 実行の直前に入力値を検証する。
     * 確認画面を経由していれば、保存しておいた入力値をリクエストに戻してから検証する。
     * そのときのエラーの戻り先は、確認画面（POST専用）ではなく指定のルートにする。
     *
     * @param  callable(): mixed  $validate
     * @param  array<string, mixed>  $backRouteParameters
     */
    protected function validateBeforeExecute(string $action, mixed $id, callable $validate, string $backRoute, array $backRouteParameters = []): mixed
    {
        if (! $this->hasConfirmInSession($action, $id)) {
            return $validate();
        }

        // フィルタが二重にかからないよう、検証前の入力値があればそちらを戻して検証し直す
        $rawInput = session($this->viewPrefix.'.confirmContext')['input'] ?? null;
        $this->request->merge(is_array($rawInput) ? $rawInput : session($this->viewPrefix.'.confirm'));

        try {
            return $validate();
        } catch (ValidationException $e) {
            $backRouteName = prefix().'.'.$backRoute;
            if (Route::has($backRouteName)) {
                $e->redirectTo(route($backRouteName, $backRouteParameters));
            }

            throw $e;
        }
    }

    protected function normalizeConfirmId(mixed $id): ?string
    {
        return $id === null ? null : (string) $id;
    }
}
