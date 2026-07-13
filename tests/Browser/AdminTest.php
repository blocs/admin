<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminTest extends DuskTestCase
{
    use LoginTestTrait;
    use ProfileTestTrait;
    use UserTestTrait;

    public function test_admin(): void
    {
        $this->browse(function (Browser $browser) {
            $this->login($browser, 'admin', 'admin');

            $email = fake()->email();
            $password = fake()->password();

            $this->createUser($browser, $email, $password);
            $this->logout($browser);

            $this->login($browser, $email, $password);
            $this->createUser($browser, $email, $password);
            $browser->assertSee('このユーザーIDはすでに登録されています。');

            $name = fake()->name();
            $this->updateUser($browser, $email, $name);
            $this->updateAvatar($browser, $email);

            $this->invalidUser($browser, $email);
            $this->validUser($browser, $email);
            $this->deleteUser($browser, $email);

            $this->createUser($browser, $email, $password);
            $this->destroyUser($browser, $email);
            $this->logout($browser);

            $this->login($browser, $email, $password);
            $browser->assertSee('ログインに失敗しました。');
        });
    }
}
