<?php

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

trait MacroTrait
{
    protected function macro(): void
    {
        Browser::macro('clickTableCell', function ($rows, $cols, $add = null) {
            $cell = '//*[@id="inmaincontents"]/form[2]/div/div[1]/table/tbody/tr['.$rows.']/td['.$cols.']';
            isset($add) && $cell .= '/'.$add;

            return $this->clickAtXPath($cell);
        });

        Browser::macro('clickMenu', function ($menu) {
            return $this->clickLink($menu)->assertSee($menu);
        });
    }
}
