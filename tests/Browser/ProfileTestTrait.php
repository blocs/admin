<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;

trait ProfileTestTrait
{
    private function updateAvatar(Browser $browser, string $email): void
    {
        $userId = User::query()->where('email', $email)->value('id');

        $browser->visit("/admin/profile/{$userId}/edit")->pause(500)
            ->scrollIntoView('footer')->pause(500)
            ->attach('input.dz-hidden-input', base_path('tests/Browser/upload/logo.png'))->pause(500)
            ->click('button[data-bs-target="#modalUpdate"]')->pause(500)
            ->whenAvailable('#modalUpdate', function ($modal) {
                $modal->click('button.btn.btn-primary')->pause(500);
            });
    }
}
