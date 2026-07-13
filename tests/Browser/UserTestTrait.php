<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;

trait UserTestTrait
{
    private function createUser(Browser $browser, string $email, string $password): void
    {
        $browser->visit('/admin/user/create')->pause(500)
            ->type('email', $email)
            ->type('password', $password)
            ->type('repassword', $password)
            ->type('name', fake()->name())
            ->click('input.form-check-input[name="role[]"][value="admin"]')->pause(500)
            ->click('button[data-bs-target="#modalStore"]')->pause(500);

        $this->confirmModal($browser, '#modalStore');
    }

    private function searchUser(Browser $browser, string $email): void
    {
        $browser->visit('/admin/user')->pause(500);

        try {
            $browser->click('a.summary-search, i.fa-search')->pause(500);
        } catch (\Throwable) {
        }

        $browser->type('input[name="search"]', $email)
            ->click('button.btn.btn-outline-secondary.m-0')
            ->pause(500);
    }

    private function updateUser(Browser $browser, string $email, string $name): void
    {
        $this->searchUser($browser, $email);

        $browser->click('table.dataTable-table tbody tr:first-child a[href$="/edit"]')->pause(500)
            ->type('name', $name)
            ->click('button[data-bs-target="#modalUpdate"]')->pause(500);

        $this->confirmModal($browser, '#modalUpdate');
        $this->clearFilters($browser);
    }

    private function destroyUser(Browser $browser, string $email): void
    {
        $this->searchUser($browser, $email);

        $browser->click('table.dataTable-table tbody tr:first-child td.text-nowrap a:nth-of-type(3)')->pause(500)
            ->click('button[data-bs-target="#modalDestroy"]')->pause(500);

        $this->confirmModal($browser, '#modalDestroy', 'button.btn.btn-danger');
        $this->clearFilters($browser);
    }

    private function deleteUser(Browser $browser, string $email): void
    {
        $this->searchUser($browser, $email);

        $browser->click('input[name="users[0][selectedRows][]"]')->pause(500)
            ->click('button[data-bs-target="#modalDestroy"]')->pause(500);

        $this->confirmModal($browser, '#modalDestroy', 'button.btn.btn-danger');
        $this->clearFilters($browser);
    }

    private function invalidUser(Browser $browser, string $email): void
    {
        $this->searchUser($browser, $email);

        $browser->click('table.dataTable-table tbody tr:first-child a[data-bs-target="#modalInactivate"]')->pause(500);

        $this->confirmModal($browser, '#modalInactivate', 'button.btn.btn-warning');
        $this->clearFilters($browser);
    }

    private function validUser(Browser $browser, string $email): void
    {
        $this->searchUser($browser, $email);

        $browser->click('table.dataTable-table tbody tr:first-child a[data-bs-target="#modalActivate"]')->pause(500);

        $this->confirmModal($browser, '#modalActivate', 'button.btn-success');
        $this->clearFilters($browser);
    }

    private function confirmModal(Browser $browser, string $selector, string $button = 'button.btn.btn-primary'): void
    {
        $browser->whenAvailable($selector, function ($modal) use ($button) {
            $modal->click($button)->pause(500);
        });
    }

    private function clearFilters(Browser $browser): void
    {
        $browser->click('button.clear')->pause(500);
    }
}
