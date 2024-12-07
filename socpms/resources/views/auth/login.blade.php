@extends('layouts.app')

@section('content')
<div class="d-lg-flex min-vh-100">
    <div class="bg order-1 order-md-2 d-none d-md-block w-50"
         style="background-image: url('{{ asset('images/login-bg.png') }}');">
    </div>
    <div class="contents order-2 order-md-1 w-50">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="fw-bold mb-2">Welcome Back</h3>
                            <p class="text-muted mb-4">Please log in to your account</p>

                            <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
                                @csrf
                                <!-- Email field -->
                                <div class="mb-4">
                                    <label for="email" class="form-label fw-medium">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-envelope text-muted"></i>
                                        </span>
                                        <input type="email"
                                               class="form-control form-control-lg border-start-0 ps-0 @error('email') is-invalid @enderror"
                                               name="email"
                                               value="{{ old('email') }}"
                                               required>
                                    </div>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <!-- Password field -->
                                <div class="mb-4">
                                    <label for="password" class="form-label fw-medium">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password"
                                               class="form-control form-control-lg border-start-0 ps-0 @error('password') is-invalid @enderror"
                                               name="password"
                                               required>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Sign In
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4">
                                <p class="text-muted mb-0">
                                    Don't have an account?
                                    <a href="{{ route('register') }}" class="text-primary fw-medium">Register</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
