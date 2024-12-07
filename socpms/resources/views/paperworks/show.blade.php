@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h4 class="card-title mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                View Paperwork Details
            </h4>
        </div>
        <div class="card-body p-4">
            <form class="needs-validation" novalidate>
                <!-- Form fields -->
                <div class="row mb-4">
                    <label class="col-sm-3 col-form-label fw-medium">Paperwork Type:</label>
                    <div class="col-sm-9">
                        <input type="text"
                            class="form-control form-control-lg shadow-sm"
                            value="{{ $paperwork->ppw_type }}"
                            readonly>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-sm-3 col-form-label fw-medium">Session:</label>
                    <div class="col-sm-9">
                        <input type="text"
                            class="form-control form-control-lg shadow-sm"
                            value="{{ $paperwork->session }}"
                            readonly>
                    </div>
                </div>

                <!-- Add other fields similarly -->

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ url()->previous() }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    @if(Auth::user()->user_type === 'admin')
                        <form action="{{ route('paperworks.approve', $paperwork->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Approve
                            </button>
                        </form>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
