@extends('layouts.admin')

@section('content')
<div class="container py-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="fas fa-users text-primary me-2"></i>
                    Manage Accounts
                </h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">User Type</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="px-4">{{ $user->id }}</td>
                                <td class="px-4">{{ $user->name }}</td>
                                <td class="px-4">{{ $user->email }}</td>
                                <td class="px-4">{{ ucfirst($user->user_type) }}</td>
                                <td class="px-4">
                                    <button class="btn btn-sm btn-primary" onclick="editUser({{ $user->id }})">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.add-user-modal')
@include('admin.partials.edit-user-modal')
@endsection
