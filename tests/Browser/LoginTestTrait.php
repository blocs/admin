<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;

trait LoginTestTrait
{
    private function login(Browser $browser, string $email, string $password): void
    {
        $browser->visit('/login')->pause(500)
            ->type('email', $email)
            ->type('password', $password)
            ->click('button[type="submit"]')->pause(500);
    }

    private function logout(Browser $browser): void
    {
        $browser->visit('/logout')->pause(500);
    }
}
