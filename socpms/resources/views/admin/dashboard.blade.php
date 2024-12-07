@extends('layouts.admin')

@section('content')
<div class="container py-5">
    <!-- Welcome Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="card-title h4 mb-3">
                <i class="fas fa-wave-square text-primary me-2"></i>
                Welcome to SOC Paperwork Management System
            </h2>
            <p class="card-text text-muted mb-0">
                Manage and track your paperwork efficiently with our comprehensive system.
            </p>
        </div>
    </div>

    <!-- Paperworks Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0">
                <i class="fas fa-clipboard-list text-primary me-2"></i>
                Paperworks Pending Approval
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Staff ID</th>
                            <th class="px-4 py-3">Session</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paperworks as $paperwork)
                            <tr>
                                <td class="px-4">{{ $paperwork->ppw_id }}</td>
                                <td class="px-4">{{ $paperwork->name }}</td>
                                <td class="px-4">{{ $paperwork->id }}</td>
                                <td class="px-4">{{ $paperwork->session }}</td>
                                <td class="px-4">
                                    <a href="{{ route('paperworks.show', $paperwork) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">No paperworks found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $paperworks->links() }}
    </div>
</div>
@endsection


