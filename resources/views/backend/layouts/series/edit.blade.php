@extends('backend.master')

@section('page_title', 'Edit Series - ' . $content->title)

@section('content')
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Series Details</h5>
                    <a href="{{ route('series.index') }}" class="btn btn-light btn-sm">Back to List</a>
                </div>

                <form action="{{ route('series.update', $content->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Series Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control"
                                    value="{{ old('title', $content->title) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="4" class="form-control">{{ old('description', $content->description) }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Thumbnail</label>
                                <input type="file" name="thumbnail" class="form-control dropify" accept="image/*"
                                    data-height="170"
                                    data-default-file="{{ $content->thumbnail ? asset($content->thumbnail) : '' }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Banner</label>
                                <input type="file" name="banner" class="form-control dropify" accept="image/*"
                                    data-height="170"
                                    data-default-file="{{ $content->banner ? asset($content->banner) : '' }}">
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <div class="form-check form-switch me-3">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active"
                                        value="1" {{ old('is_active', $content->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Active Series</label>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm">Update Series</button>
                                <a href="{{ route('episodes.create', $content->id) }}" class="btn btn-success btn-sm">
                                    <i class="ti ti-plus me-1"></i>Add Episode
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Episodes</h5>
                    <span class="badge bg-primary-subtle text-primary">{{ $content->episodes->count() }}</span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm text-nowrap">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th>
                                    <th>Episode</th>
                                    <th>Access</th>
                                    <th>Coins</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($content->episodes as $episode)
                                    <tr>
                                        <td>{{ $episode->episode_number ?? '-' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $episode->title ?: 'Untitled Episode' }}</div>
                                            <small class="text-muted text-uppercase">{{ $episode->video_type }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info text-uppercase">
                                                {{ $episode->access_type ?? $content->access_type }}
                                            </span>
                                        </td>
                                        <td>
                                            @if (($episode->access_type ?? $content->access_type) === 'coins')
                                                {{ (int) ($episode->coins_required ?? 0) }}
                                            @else
                                                0
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $episode->is_active ? 'success-subtle text-success' : 'warning-subtle text-warning' }}">
                                                {{ $episode->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('episodes.edit', [$content->id, $episode->id]) }}"
                                                    class="btn btn-default btn-icon btn-sm" title="Edit Episode">
                                                    <i class="ti ti-edit fs-lg"></i>
                                                </a>

                                                <button type="button"
                                                    class="btn {{ $episode->is_active ? 'btn-warning' : 'btn-success' }} btn-icon btn-sm js-episode-status-toggle"
                                                    title="{{ $episode->is_active ? 'Disable' : 'Enable' }} Episode"
                                                    data-url="{{ route('episodes.status.toggle', [$content->id, $episode->id]) }}"
                                                    data-status="{{ $episode->is_active ? 0 : 1 }}"
                                                    data-action-label="{{ $episode->is_active ? 'Disable' : 'Enable' }}"
                                                    data-episode-title="{{ $episode->title ?: 'Episode #' . $episode->episode_number }}">
                                                    <i class="ti ti-power fs-lg"></i>
                                                </button>

                                                <button type="button"
                                                    class="btn btn-danger btn-icon btn-sm js-episode-delete"
                                                    title="Delete Episode"
                                                    data-url="{{ route('episodes.destroy', [$content->id, $episode->id]) }}"
                                                    data-episode-title="{{ $episode->title ?: 'Episode #' . $episode->episode_number }}">
                                                    <i class="ti ti-trash fs-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No episodes yet. Use Add
                                            Episode to start.</td>
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
            $(document).on('click', '.js-episode-status-toggle', function() {
                const url = $(this).data('url');
                const status = $(this).data('status');
                const actionLabel = $(this).data('action-label');
                const episodeTitle = $(this).data('episode-title');

                Swal.fire({
                    title: `${actionLabel} Episode?`,
                    text: `You are about to ${actionLabel.toLowerCase()} ${episodeTitle}.`,
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

            $(document).on('click', '.js-episode-delete', function() {
                const url = $(this).data('url');
                const episodeTitle = $(this).data('episode-title');

                Swal.fire({
                    title: 'Delete Episode?',
                    html: `This will delete <strong>${episodeTitle}</strong>.<br>Please enter your admin password to continue.`,
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
