<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 fw-bold text-primary">{{ $title }}</h6>
        @if (!empty($description))
            <small class="text-muted">{{ $description }}</small>
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 w-100">
                <thead>
                    <tr>
                        @foreach ($columns as $col)
                            <th>{{ $col['label'] }}</th>
                        @endforeach
                        <th class="text-end">{{ __('Viste') }}</th>
                        <th class="text-end">{{ __('Univoci') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            @foreach ($columns as $col)
                                @php $k = $col['key']; @endphp
                                <td class="{{ !empty($col['mono']) ? 'font-monospace small' : '' }} {{ ($k === 'path') ? 'text-truncate' : '' }}" style="{{ ($k === 'path') ? 'max-width: 12rem;' : '' }}" title="{{ $row[$k] ?? '' }}">{{ $row[$k] ?? '' }}</td>
                            @endforeach
                            <td class="text-end font-monospace">{{ $row[$valueKeys[0]] ?? '' }}</td>
                            <td class="text-end font-monospace">{{ $row[$valueKeys[1]] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
