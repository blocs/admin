<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TEST_NAME extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testExample(): void
    {
        $path = 'ROUTE_PREFIX';
        $model = 'App\Models\MODEL_NAME';

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
        $this->withoutMiddleware(\Blocs\Middleware\Role::class);

        // 一覧画面と検索
        $response = $this->get($path.'/');
        $response->assertStatus(200);

        $response = $this->post($path.'/search', [
            'search' => 'test',
        ]);
        $response->assertStatus(200);

        // 新規作成
        $response = $this->get($path.'/create');
        $response->assertStatus(200);

        $response = $this->post($path.'/', [
            FORM_LIST,
        ]);
        $response->assertStatus(302);

        $id = $model::max('id');

        // 編集
        $response = $this->get($path.'/'.$id.'/edit');
        $response->assertStatus(200);

        $response = $this->post($path.'/'.$id, [
            FORM_LIST,
        ]);
        $response->assertStatus(302);

        // 削除
        $response = $this->post($path.'/'.$id.'/destroy');
        $response->assertStatus(302);
    }
}
