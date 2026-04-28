@extends('backend.master')

@section('page_title', 'Episode Stats')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <form method="GET" action="{{ route('episode.stats') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="series_id" class="form-label mb-1">Series</label>
                            <select name="series_id" id="series_id" class="form-select">
                                <option value="">All Series</option>
                                @foreach ($seriesList as $series)
                                    <option value="{{ $series->id }}"
                                        {{ (int) ($filters['series_id'] ?? 0) === $series->id ? 'selected' : '' }}>
                                        {{ $series->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="episode_id" class="form-label mb-1">Episode</label>
                            <select name="episode_id" id="episode_id" class="form-select"
                                {{ empty($filters['series_id']) ? 'disabled' : '' }}>
                                <option value="">All Episodes</option>
                                @foreach ($episodeOptions as $episode)
                                    <option value="{{ $episode->id }}"
                                        {{ (int) ($filters['episode_id'] ?? 0) === $episode->id ? 'selected' : '' }}>
                                        #{{ $episode->episode_number }} - {{ $episode->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="search" class="form-label mb-1">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="{{ $filters['search'] ?? '' }}" placeholder="Series or episode title">
                        </div>

                        <div class="col-md-2">
                            <label for="sort" class="form-label mb-1">Sort By</label>
                            <select name="sort" id="sort" class="form-select">
                                <option value="top_likes" {{ ($filters['sort'] ?? '') === 'top_likes' ? 'selected' : '' }}>
                                    Top Likes</option>
                                <option value="top_saves" {{ ($filters['sort'] ?? '') === 'top_saves' ? 'selected' : '' }}>
                                    Top Saves</option>
                                <option value="top_gifts" {{ ($filters['sort'] ?? '') === 'top_gifts' ? 'selected' : '' }}>
                                    Top Gifts</option>
                                <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest
                                </option>
                                <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest
                                </option>
                            </select>
                        </div>

                        <div class="col-md-1 d-grid">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="episodeStatsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm text-nowrap">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th>
                                    <th>Series</th>
                                    <th>Episode</th>
                                    <th>Likes</th>
                                    <th>Saves</th>
                                    <th>Gifts</th>
                                    <th>Recent Gift Senders</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="giftHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="giftHistoryTitle">Gift History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered mb-0">
                            <thead class="bg-light bg-opacity-25 text-nowrap">
                                <tr>
                                    <th>#</th>
                                    <th>Gift Type</th>
                                    <th>Sender</th>
                                    <th>Coins</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody id="giftHistoryBody">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Select an episode to view gifts.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('backend') }}/assets/plugins/jquery/jquery.min.js"></script>
    <script src="{{ asset('backend') }}/assets/plugins/datatables/dataTables.min.js"></script>
    <script src="{{ asset('backend') }}/assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('backend') }}/assets/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="{{ asset('backend') }}/assets/plugins/datatables/responsive.bootstrap5.min.js"></script>

    <script>
        $(function() {
            const table = $('#episodeStatsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ajax: {
                    url: "{{ route('episode.stats.data') }}",
                    data: function(d) {
                        d.series_id = $('#series_id').val();
                        d.episode_id = $('#episode_id').val();
                        d.search = $('#search').val();
                        d.sort = $('#sort').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'series_title',
                        name: 'content.title'
                    },
                    {
                        data: 'episode_title',
                        name: 'title'
                    },
                    {
                        data: 'likes_badge',
                        name: 'likes_count'
                    },
                    {
                        data: 'saves_badge',
                        name: 'saves_count'
                    },
                    {
                        data: 'gifts_badge',
                        name: 'gifts_count'
                    },
                    {
                        data: 'recent_senders',
                        name: 'recent_senders',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [3, 'desc']
                ]
            });

            $('#series_id').on('change', function() {
                this.form.submit();
            });

            $('#episode_id, #sort').on('change', function() {
                table.draw();
            });

            $('#search').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    table.draw();
                }
            });

            $('form[action="{{ route('episode.stats') }}"]').on('submit', function(e) {
                e.preventDefault();
                table.draw();
            });

            $(document).on('click', '.js-view-gifts', function() {
                const url = $(this).data('url');
                const modalEl = document.getElementById('giftHistoryModal');
                const modal = new bootstrap.Modal(modalEl);
                const tbody = $('#giftHistoryBody');
                const title = $('#giftHistoryTitle');

                title.text('Gift History');
                tbody.html(
                    '<tr><td colspan="5" class="text-center py-4 text-muted">Loading gift history...</td></tr>'
                    );
                modal.show();

                $.get(url)
                    .done(function(response) {
                        title.text(
                            `Gift History - #${response.episode_number} ${response.episode_title}`);

                        if (!response.gifts || response.gifts.length === 0) {
                            tbody.html(
                                '<tr><td colspan="5" class="text-center py-4 text-muted">No gifts found for this episode.</td></tr>'
                            );
                            return;
                        }

                        let rows = '';
                        response.gifts.forEach((gift, index) => {
                            rows += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">${gift.gift_type}</span></td>
                                    <td>${gift.sender}</td>
                                    <td>${gift.coins}</td>
                                    <td>${gift.sent_at}</td>
                                </tr>
                            `;
                        });

                        tbody.html(rows);
                    })
                    .fail(function() {
                        tbody.html(
                            '<tr><td colspan="5" class="text-center py-4 text-danger">Failed to load gift history.</td></tr>'
                        );
                    });
            });
        });
    </script>
@endpush
