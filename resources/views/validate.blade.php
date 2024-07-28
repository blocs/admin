<validate>
    @foreach ($validates as $validate)
        <template>
            <loc>{{ $validate['loc'] }}</loc>
            <name>{{ $validate['name'] }}</name>
            <validate>{{ $validate['validate'] }}</validate>
            <message>{{ $validate['message'] }}</message>
        </template>
    @endforeach
</validate>
