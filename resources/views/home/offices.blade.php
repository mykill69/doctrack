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
                            <h3 class="card-title">List of Registered Offices</h3>
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addOfficeModal"
                                style="margin-left: auto; margin-right: 20px;">
                                <i class="fa fa-plus"></i> Add Office
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="userTable" class="table table-bordered table-hover text-sm">
                                    <thead>
                                        <tr>
                                            <th>NO.</th>
                                            <th>OFFICE NAME</th>
                                            <th>OFFICE ABBREVIATION</th>
                                            <th>DATE CREATED</th>
                                            <th>ACTION</th>
                                        </tr>
                                        @foreach ($offices as $index => $office)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <input type="text" class="form-control bordered office-input"
                                                        data-id="{{ $office->id }}" data-field="office_name"
                                                        value="{{ $office->office_name }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control bordered office-input"
                                                        data-id="{{ $office->id }}" data-field="office_abbr"
                                                        value="{{ $office->office_abbr }}">
                                                </td>

                                                <td>{{ $office->created_at ? $office->created_at->format('Y-m-d') : '' }}
                                                </td>

                                                <td>
                                                    <div class="btn-group" role="group" aria-label="Office Actions">
                                                        {{-- <button type="button" class="btn btn-primary btn-sm"
                                                            title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button> --}}
                                                        <button type="button" class="btn btn-danger btn-sm" title="Delete"
                                                            onclick="deleteUser({{ $office->id }})">
                                                            Delete <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>


                                            </tr>
                                        @endforeach
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>


    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).on('focus', '.office-input', function() {
            $(this).data('original-value', $(this).val());
        });

        $(document).on('blur', '.office-input', function() {
            let officeId = $(this).data('id');
            let field = $(this).data('field');
            let value = $(this).val();
            let originalValue = $(this).data('original-value');

            if (value === originalValue) return;

            $.ajax({
                url: "{{ url('offices') }}/" + officeId, // will include /doctrack/public
                type: 'POST', // method override
                data: {
                    _method: 'PUT',
                    _token: $('meta[name="csrf-token"]').attr('content'), // important
                    [field]: value
                },
                success: function(response) {
                    console.log('Updated successfully:', response);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Error updating record.');
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.getElementById('error-message');
            if (errorMessage) {

                setTimeout(function() {
                    errorMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>
    <script>
        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this office?')) {
                $.ajax({
                    url: "{{ url('offices') }}/" + id,
                    type: 'POST', // method override
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        alert(response.success);
                        location.reload();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert('An error occurred: ' + xhr.responseText);
                    }
                });
            }
        }
    </script>

    <script>
        $(document).ready(function() {
            var t = $('#userTable').DataTable({
                "order": [
                    [1, 'asc']
                ], // Sort by FULLNAME
                "pageLength": 50,
                "columnDefs": [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0 // Index column
                }],
                "fnDrawCallback": function() {
                    var api = this.api();
                    api.column(0, {
                        page: 'current'
                    }).nodes().each(function(cell, i) {
                        cell.innerHTML = i + 1;
                    });
                }
            });
        });
    </script>

    <script>
        $(document).on('submit', '#addOfficeForm', function(e) {
            e.preventDefault();

            $.ajax({
                url: "{{ url('offices') }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#addOfficeModal').modal('hide');
                    alert('Office added successfully!');
                    location.reload(); // reload table
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Error adding office.');
                }
            });
        });
    </script>

    @include('modal.addOffice')
    @include('modal.addGroup')
@endsection
