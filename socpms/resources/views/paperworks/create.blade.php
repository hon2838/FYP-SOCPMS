@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="card-title mb-0">
                <i class="fas fa-plus text-primary me-2"></i>
                Create New Paperwork
            </h4>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('paperworks.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <!-- Form fields -->
                <div class="row mb-4">
                    <label class="col-sm-3 col-form-label fw-medium">Paperwork Type:</label>
                    <div class="col-sm-9">
                        <input type="text"
                            class="form-control form-control-lg shadow-sm @error('ppw_type') is-invalid @enderror"
                            name="ppw_type"
                            value="{{ old('ppw_type') }}"
                            required>
                        @error('ppw_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Add other form fields -->

                <div class="d-grid gap-2 mt-5">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>
                        Submit Paperwork
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
