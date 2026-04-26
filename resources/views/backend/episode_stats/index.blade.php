@extends('backend.master')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-4">Episode Stats</h4>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Series</th>
                        <th>Episode</th>
                        <th>Likes</th>
                        <th>Saves</th>
                        <th>Gifts</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($series as $s)
                        @foreach ($s->episodes as $ep)
                            <tr>
                                <td>{{ $s->title }}</td>
                                <td>{{ $ep->title }}</td>
                                <td>{{ $ep->likes_count ?? 0 }}</td>
                                <td>{{ $ep->saves_count ?? 0 }}</td>
                                <td>
                                    @if ($ep->gifts->count())
                                        <ul>
                                            @foreach ($ep->gifts as $gift)
                                                <li>{{ $gift->type }} ({{ $gift->user->name ?? 'Unknown' }})</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
