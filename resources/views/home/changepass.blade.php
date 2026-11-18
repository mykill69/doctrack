@extends('layouts.main')

@section('body')
    <!-- Include CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style type="text/css">
        .no-left-radius {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
    </style>
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Edit Account Details</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('passChange', $user->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="fname">First Name</label>
                                        <input type="text" name="fname" value="{{ $user->fname }}"
                                            class="form-control" readonly>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="mname">Middle Name</label>
                                        <input type="text" name="mname" value="{{ $user->mname }}"
                                            class="form-control" readonly>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="lname">Last Name</label>
                                        <input type="text" name="lname" value="{{ $user->lname }}"
                                            class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="email">Institutional Email</label>
                                        <input type="text" name="email" value="{{ $user->email }}"
                                            class="form-control" readonly>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="password">New Password</label>
                                        <input type="password" id="password" name="password" class="form-control"
                                            placeholder="Enter new password">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="password_confirmation">Confirm Password</label>
                                        <!-- Change this to password_confirmation -->
                                        <input type="password" id="confirm_password" name="password_confirmation"
                                            class="form-control" oninput="checkPasswordMatch();"
                                            placeholder="Confirm new password">
                                        <small id="passwordMatchMessage" class="text-danger" style="display:none;"></small>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label for="department">Department</label>
                                        <select class="form-control" id="department" name="department"
                                            data-placeholder="Select Offices">
                                            <option value="" disabled>Select Office</option> <!-- Default option -->
                                            @foreach ($offices as $office)
                                                <option value="{{ $office->office_name }}"
                                                    {{ isset($user) && $user->department == $office->office_name ? 'selected' : '' }}>
                                                    {{ $office->office_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="esig_file">Electronic Signature</label>
                                        <input type="file" class="form-control mb-2" name="esig_file" id="esig_file"
                                            accept="image/png, image/jpeg">

                                        <small class="form-text text-muted mb-2">
                                            PNG or JPG only (max 5MB). Your signature is stored encrypted.
                                        </small>

                                        @error('esig_file')
                                            <div class="text-danger small mb-2">{{ $message }}</div>
                                        @enderror

                                        @if ($userEsig && $userEsig->needsReupload())
                                            {{-- Legacy signature uploaded before encryption --}}
                                            <div class="alert alert-warning py-2 px-3 small mb-0">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                <strong>Please upload your e-signature again.</strong>
                                                Your previous signature was saved without encryption and is no longer
                                                used on documents until you re-upload it.
                                            </div>
                                        @elseif ($userEsig && $userEsig->esig_file)
                                            <p class="mb-1">
                                                Current E-Signature:
                                                <span class="badge badge-success"><i class="fas fa-lock"></i> Encrypted</span>
                                            </p>
                                            <img src="{{ route('esig.show') }}?v={{ $userEsig->updated_at?->timestamp }}"
                                                alt="E-signature" class="img-thumbnail" style="max-height: 120px;">
                                        @endif

                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Details</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const message = document.getElementById('passwordMatchMessage');

            if (password !== confirmPassword) {
                message.style.display = 'block';
                message.textContent = 'Passwords do not match.';
            } else {
                message.style.display = 'none';
            }
        }
    </script>
@endsection
