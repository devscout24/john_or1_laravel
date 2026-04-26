@extends('backend.master')

@section('page_title', 'Create Series')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create New Series</h5>
                    <a href="{{ route('series.index') }}" class="btn btn-light btn-sm">Back to List</a>
                </div>

                <form action="{{ route('series.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Series Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                                    required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Thumbnail</label>
                                <input type="file" name="thumbnail" class="form-control dropify" accept="image/*"
                                    data-height="170">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Banner</label>
                                <input type="file" name="banner" class="form-control dropify" accept="image/*"
                                    data-height="170">
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active"
                                        value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Active Series</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Create Series</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
