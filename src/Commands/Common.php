<?php

namespace Blocs\Commands;

use Illuminate\Http\Request;

class MatchRoute extends \Illuminate\Routing\RouteCollection
{
    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod());
        $route = $this->matchAgainstRoutes($routes, $request);

        return $route;
    }
}

class Common
{
    private static $route;

    // ルートを取得
    public static function getRoute($url)
    {
        if (empty(self::$route)) {
            self::$route = new MatchRoute();
            foreach (\Route::getRoutes() as $route) {
                self::$route->add($route);
            }
        }

        $request = \Request::create($url);
        $route = self::$route->match($request);
        if (empty($route)) {
            return false;
        }

        return $route;
    }
}
