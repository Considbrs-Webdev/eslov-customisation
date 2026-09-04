@paper([
    'classList' => [
        'eslov-import-review-changes',
        'u-margin__top--4',
        'u-padding__x--3',
        'u-padding__y--3',
        'u-padding__x--4@md',
        'u-padding__y--4@md'
    ]
])
    @typography([
        'element' => 'h2',
        'variant' => 'h4',
        'classList' => ['u-margin__top--0']
    ])
        {{ $heading }}
    @endtypography
    <div class="eslov-import-review-changes__table-wrap">
        <table class="eslov-import-review-changes__table">
            <thead>
                <tr>
                    <th scope="col">{{ $columnField }}</th>
                    <th scope="col">{{ $columnBefore }}</th>
                    <th scope="col">{{ $columnAfter }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <th scope="row">{{ $row['field'] }}</th>
                        <td class="eslov-import-review-changes__value">{{ $row['before'] }}</td>
                        <td class="eslov-import-review-changes__value">{{ $row['after'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endpaper
