@extends('backend.master')

@section('page_title', 'Series Management')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Series</h5>
                    <a href="{{ route('series.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus me-1"></i>Create Series
                    </a>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm text-nowrap">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th>
                                    <th>Series</th>
                                    <th>Episodes</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 1%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($series as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $item->thumbnail ? asset($item->thumbnail) : asset('backend/assets/images/logo-sm.png') }}"
                                                    alt="series-image" class="rounded" width="46" height="46"
                                                    style="object-fit: cover;">
                                                <div>
                                                    <div class="fw-semibold">{{ $item->title }}</div>
                                                    <small class="text-muted">Updated
                                                        {{ $item->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-primary-subtle text-primary">{{ $item->episodes_count }}</span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $item->is_active ? 'success-subtle text-success' : 'warning-subtle text-warning' }}">
                                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('series.edit', $item->id) }}"
                                                    class="btn btn-default btn-icon btn-sm" title="Edit Series">
                                                    <i class="ti ti-edit fs-lg"></i>
                                                </a>

                                                <a href="{{ route('episodes.create', $item->id) }}"
                                                    class="btn btn-primary btn-icon btn-sm" title="Add Episode">
                                                    <i class="ti ti-video-plus fs-lg"></i>
                                                </a>

                                                <button type="button"
                                                    class="btn {{ $item->is_active ? 'btn-warning' : 'btn-success' }} btn-icon btn-sm js-series-status-toggle"
                                                    title="{{ $item->is_active ? 'Disable' : 'Enable' }} Series"
                                                    data-url="{{ route('series.status.toggle', $item->id) }}"
                                                    data-status="{{ $item->is_active ? 0 : 1 }}"
                                                    data-action-label="{{ $item->is_active ? 'Disable' : 'Enable' }}"
                                                    data-series-title="{{ $item->title }}">
                                                    <i class="ti ti-power fs-lg"></i>
                                                </button>

                                                <button type="button"
                                                    class="btn btn-danger btn-icon btn-sm js-series-delete"
                                                    title="Delete Series"
                                                    data-url="{{ route('series.destroy', $item->id) }}"
                                                    data-series-title="{{ $item->title }}">
                                                    <i class="ti ti-trash fs-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No series found. Create your first series.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $(document).on('click', '.js-series-status-toggle', function() {
                const url = $(this).data('url');
                const status = $(this).data('status');
                const actionLabel = $(this).data('action-label');
                const seriesTitle = $(this).data('series-title');

                Swal.fire({
                    title: `${actionLabel} Series?`,
                    text: `You are about to ${actionLabel.toLowerCase()} ${seriesTitle}.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: actionLabel,
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    const form = $('<form>', {
                        method: 'POST',
                        action: url
                    });

                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'status',
                        value: status
                    }));

                    $('body').append(form);
                    form.trigger('submit');
                });
            });

            $(document).on('click', '.js-series-delete', function() {
                const url = $(this).data('url');
                const seriesTitle = $(this).data('series-title');

                Swal.fire({
                    title: 'Delete Series?',
                    html: `This will permanently delete <strong>${seriesTitle}</strong> and all episodes.<br>Please enter your admin password to continue.`,
                    icon: 'warning',
                    input: 'password',
                    inputPlaceholder: 'Enter your current password',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    confirmButtonColor: '#d33',
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password is required');
                            return false;
                        }
                        return password;
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    const form = $('<form>', {
                        method: 'POST',
                        action: url
                    });

                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'current_password',
                        value: result.value
                    }));

                    $('body').append(form);
                    form.trigger('submit');
                });
            });
        });
    </script>
@endpush
