@extends('backend.master')

@section('page_title', 'Edit Episode - ' . ($episode->title ?: 'Episode #' . $episode->episode_number))

@section('content')
    <div class="row">
        <div class="col-xl-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Episode for {{ $content->title }}</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                            class="btn {{ $episode->is_active ? 'btn-warning' : 'btn-success' }} btn-sm js-episode-status-toggle"
                            data-url="{{ route('episodes.status.toggle', [$content->id, $episode->id]) }}"
                            data-status="{{ $episode->is_active ? 0 : 1 }}"
                            data-action-label="{{ $episode->is_active ? 'Disable' : 'Enable' }}"
                            data-episode-title="{{ $episode->title ?: 'Episode #' . $episode->episode_number }}">
                            {{ $episode->is_active ? 'Disable' : 'Enable' }} Episode
                        </button>

                        <button type="button" class="btn btn-danger btn-sm js-episode-delete"
                            data-url="{{ route('episodes.destroy', [$content->id, $episode->id]) }}"
                            data-episode-title="{{ $episode->title ?: 'Episode #' . $episode->episode_number }}">
                            Delete Episode
                        </button>

                        <a href="{{ route('series.edit', $content->id) }}" class="btn btn-light btn-sm">Back to Series</a>
                    </div>
                </div>

                <form action="{{ route('episodes.update', [$content->id, $episode->id]) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Episode Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control"
                                    value="{{ old('title', $episode->title) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Episode Number (Auto)</label>
                                <input type="text" class="form-control" value="{{ $episode->episode_number }}" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Access Type <span class="text-danger">*</span></label>
                                <select name="access_type" id="accessType" class="form-select" required>
                                    <option value="free"
                                        {{ old('access_type', $episode->access_type ?? $content->access_type) === 'free' ? 'selected' : '' }}>
                                        Free</option>
                                    <option value="coins"
                                        {{ old('access_type', $episode->access_type ?? $content->access_type) === 'coins' ? 'selected' : '' }}>
                                        Coins</option>
                                    <option value="ads"
                                        {{ old('access_type', $episode->access_type ?? $content->access_type) === 'ads' ? 'selected' : '' }}>
                                        Ads</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Video Source <span class="text-danger">*</span></label>
                                <select name="video_type" id="videoType" class="form-select" required>
                                    <option value="external"
                                        {{ old('video_type', $episode->video_type) === 'external' ? 'selected' : '' }}>
                                        External URL</option>
                                    <option value="uploaded"
                                        {{ old('video_type', $episode->video_type) === 'uploaded' ? 'selected' : '' }}>
                                        Upload Video</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Duration (seconds)</label>
                                <input type="number" name="duration" min="0" class="form-control"
                                    value="{{ old('duration', $episode->duration) }}">
                            </div>

                            <div class="col-md-6" id="coinsWrapper">
                                <label class="form-label">Episode Coins</label>
                                <input type="number" name="coins_required" min="0" class="form-control"
                                    value="{{ old('coins_required', $episode->coins_required ?? 0) }}">
                            </div>

                            <div class="col-12" id="externalUrlGroup">
                                <label class="form-label">Video URL</label>
                                <input type="url" name="video_url" id="videoUrl" class="form-control"
                                    placeholder="https://example.com/video.mp4"
                                    value="{{ old('video_url', $episode->video_url) }}">
                            </div>

                            <div class="col-12" id="uploadedVideoGroup">
                                <label class="form-label">Upload Video File</label>
                                <input type="file" name="video_file" id="videoFile" class="form-control"
                                    accept="video/*">
                                <small class="text-muted">Leave empty to keep existing uploaded file.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Preview</label>
                                <div class="border rounded p-2 bg-light-subtle">
                                    <video id="videoPreview" controls style="width: 100%; max-height: 320px;"
                                        hidden></video>
                                    <div id="previewEmpty" class="text-muted small">Video preview will appear here.</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                                        value="1" {{ old('is_active', $episode->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Active Episode</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Update Episode</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const existingExternalUrl = @json($episode->video_url);
            const existingUploadedUrl = @json($episode->storage_path ? asset($episode->storage_path) : null);
            const $accessType = $('#accessType');
            const $videoType = $('#videoType');
            const $externalGroup = $('#externalUrlGroup');
            const $uploadGroup = $('#uploadedVideoGroup');
            const $coinsWrapper = $('#coinsWrapper');
            const preview = document.getElementById('videoPreview');
            const empty = document.getElementById('previewEmpty');
            const fileInput = document.getElementById('videoFile');
            const urlInput = document.getElementById('videoUrl');

            function syncAccessTypeView() {
                const type = $accessType.val();
                if (type === 'coins') {
                    $coinsWrapper.show();
                } else {
                    $coinsWrapper.hide();
                }
            }

            function showPreview(src) {
                if (!src) {
                    preview.hidden = true;
                    preview.removeAttribute('src');
                    preview.load();
                    empty.style.display = 'block';
                    return;
                }

                preview.hidden = false;
                preview.src = src;
                empty.style.display = 'none';
            }

            function syncVideoSourceView() {
                const type = $videoType.val();
                if (type === 'uploaded') {
                    $uploadGroup.show();
                    $externalGroup.hide();
                    showPreview(fileInput.files[0] ? URL.createObjectURL(fileInput.files[0]) : existingUploadedUrl);
                } else {
                    $uploadGroup.hide();
                    $externalGroup.show();
                    showPreview(urlInput.value.trim() || existingExternalUrl || null);
                }
            }

            $accessType.on('change', syncAccessTypeView);
            $videoType.on('change', syncVideoSourceView);

            $('#videoFile').on('change', function() {
                if ($videoType.val() !== 'uploaded') return;
                showPreview(this.files[0] ? URL.createObjectURL(this.files[0]) : existingUploadedUrl);
            });

            $('#videoUrl').on('input', function() {
                if ($videoType.val() !== 'external') return;
                showPreview(this.value.trim() || null);
            });

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

            syncAccessTypeView();
            syncVideoSourceView();
        });
    </script>
@endpush
