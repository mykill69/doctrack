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
                        <form action="{{ route('passChange', $user->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="fname">First Name</label>
                                    <input type="text" name="fname" value="{{ $user->fname }}" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="mname">Middle Name</label>
                                    <input type="text" name="mname" value="{{ $user->mname }}" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="lname">Last Name</label>
                                    <input type="text" name="lname" value="{{ $user->lname }}" class="form-control">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="username">Username</label>
                                    <input type="text" name="username" value="{{ $user->username }}" class="form-control">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="password">New Password</label>
                                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter new password">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="password_confirmation">Confirm Password</label> <!-- Change this to password_confirmation -->
                                    <input type="password" id="confirm_password" name="password_confirmation" class="form-control" oninput="checkPasswordMatch();" placeholder="Confirm new password">
                                    <small id="passwordMatchMessage" class="text-danger" style="display:none;"></small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="department">Department</label>
                                    <select class="form-control" id="department" name="department" data-placeholder="Select Offices">
                                        <option value="" disabled>Select Office</option> <!-- Default option -->
                                        @foreach($offices as $office)
                                        <option value="{{ $office->office_name }}" {{ isset($user) && $user->department == $office->office_name ? 'selected' : '' }}>
                                            {{ $office->office_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- <div class="form-group col-md-6">
                                    <label for="role">Role</label>
                                    <select class="form-control" id="role" name="role" data-placeholder="Select Role">
                                        <option value="{{ $user->role }}" selected>{{ $user->role }}</option>
                                        <option value="Administrator">Administrator</option>
                                        <option value="super_user">Super User</option>
                                        <option value="records_officer">Records Officer</option>
                                        <option value="staff">Staff</option>
                                    </select>
                                </div>--}}
                            </div> 
                            <button type="submit" class="btn btn-primary">Update User</button>
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

@endsection
