<route>
    @foreach ($routes as $route)
        <url>
            <loc>{{ url($route['url']) }}</loc>
            <method>{{ $route['method'] }}</method>
            <controller>{{ $route['controller'] }}</controller>
            <name>{{ $route['name'] }}</name>
            <middlewares>{{ is_array($route['middlewares']) ? implode(', ', $route['middlewares']) : $route['middlewares'] }}</middlewares>
        </url>
    @endforeach
</route>
