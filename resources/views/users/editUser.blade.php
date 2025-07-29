@extends('layouts.main')
@section('body')
    <!-- Include CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style type="text/css">
        .no-left-radius {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        /* Ensure Select2 matches Bootstrap input height */
.select2-container--default .select2-selection--multiple {
    height: auto !important;
    min-height: 38px !important;
    padding: 0.2rem 0.75rem !important;
    border: 1px solid #ced4da !important;
    border-radius: 0.25rem !important;
    line-height: 1.5 !important;
    font-size: 1rem !important;
    box-sizing: border-box;
}

/* Fix vertical alignment of tags inside the selection box */
.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    padding: 0 !important;
}

    </style>
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Edit User Details</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('userUpdate', $user->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="fname">First Name</label>
                                        <input type="text" name="fname" value="{{ $user->fname }}"
                                            class="form-control">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="mname">Middle Name</label>
                                        <input type="text" name="mname" value="{{ $user->mname }}"
                                            class="form-control">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="lname">Last Name</label>
                                        <input type="text" name="lname" value="{{ $user->lname }}"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="email">Institutional Email</label>
                                        <input type="text" name="email" value="{{ $user->email }}"
                                            class="form-control">
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
                                    {{-- Department --}}
                                    <div class="form-group col-md-4">
                                        <label for="department">Department</label>
                                        <select class="form-control" id="department" name="department">
                                            <option value="" disabled
                                                {{ empty($user->department) ? 'selected' : '' }}>Select Office</option>
                                            @foreach ($offices as $office)
                                                <option value="{{ $office->office_name }}"
                                                    {{ $user->department == $office->office_name ? 'selected' : '' }}>
                                                    {{ $office->office_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Role --}}
                                    <div class="form-group col-md-4">
                                        <label for="role">Role</label>
                                        <select class="form-control" id="role" name="role">
                                            <option value="" disabled {{ empty($user->role) ? 'selected' : '' }}>
                                                Select Role</option>
                                            <option value="super_user" {{ $user->role == 'super_user' ? 'selected' : '' }}>
                                                Super User</option>
                                            <option value="records_officer"
                                                {{ $user->role == 'records_officer' ? 'selected' : '' }}>Records Officer
                                            </option>
                                            <option value="staff" {{ $user->role == 'staff' ? 'selected' : '' }}>Personnel
                                            </option>
                                        </select>
                                    </div>

                                    {{-- Position --}}
                                    {{-- <div class="form-group col-md-3">
                                        <label for="position">Position/Designation</label>
                                        @php
                                            $positions = [
                                                1 => 'President',
                                                2 => 'VPAA',
                                                3 => 'VPAF',
                                                4 => 'Office Heads',
                                                5 => 'Deans',
                                                6 => 'Campus Administrators',
                                                7 => 'Directors',
                                            ];
                                        @endphp
                                        <select name="position" class="form-control">
                                            <option value="" disabled {{ empty($user->position) ? 'selected' : '' }}>
                                                Select Position</option>
                                            @foreach ($positions as $key => $label)
                                                <option value="{{ $key }}"
                                                    {{ $user->position == $key ? 'selected' : '' }}>{{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div> --}}

                                    {{-- Groups --}}
                                    <div class="form-group col-md-4">
                                        <label for="group">Group(s)</label>
                                        <select class="form-control select2" name="group_id[]" id="group" multiple>
                                            @foreach ($groups as $group)
                                                <option value="{{ $group->id }}"
                                                    {{ $user->groups->contains('id', $group->id) ? 'selected' : '' }}>
                                                    {{ $group->group_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>


                                <button type="submit" class="btn btn-primary">Update User</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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


    <script>
        $(document).ready(function() {
            $('#group').select2({
                theme: 'bootstrap4',
                placeholder: 'Select Group(s)',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endsection
