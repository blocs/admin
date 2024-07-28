<database>
    @foreach ($databases as $database)
        <schema>
            <name>{{ $database['name'] }}</name>
            <column>{{ $database['column'] }}</column>
            <type>{{ $database['type'] }}</type>
        </schema>
    @endforeach
</database>
