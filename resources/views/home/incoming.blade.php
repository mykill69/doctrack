@extends('layouts.main')
@php
    use App\Models\Log; // Ensure you import your Log model here
@endphp
<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff !important;
        border-color: #187744 !important;
        color: #fff;
        padding: 0 10px;
        margin-top: 0.31rem;
    }
</style>
@section('body')
    {{-- Show error message if search fails --}}
    @if (session('error'))
        <div class="alert alert-danger mt-2">
            {{ session('error') }}
        </div>
    @endif
    <div class="content-wrapper">

        <!-- Main content -->
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">SEARCH DOCUMENT TRACKING CODE</h3>
                            </div>
                            <div class="card-body" style="height: 800px;">
                                <div class="input-group">
                                    <div class="col-md-12 ">
                                        <form id="slipSearchForm"> {{-- Add ID here, no action/method --}}
                                            <div class="input-group">
                                                <input type="search" id="query" name="query"
                                                    class="form-control form-control-lg"
                                                    placeholder="Search Tracking Code here..." required>
                                                {{-- Add ID here --}}
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-lg btn-default">
                                                        <i class="fa fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                        <iframe id="slipFrame" src=""
                                            style="width:100%; height:700px; border:none; display: none;"></iframe>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- JavaScript for AJAX Search --}}
    <script>
        document.getElementById('slipSearchForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const query = document.getElementById('query').value;

            fetch("{{ route('search') }}?query=" + query)
                .then(response => {
                    if (response.redirected) {
                        // If tracking code is valid, show iframe
                        document.getElementById('slipFrame').style.display = 'block';
                        document.getElementById('slipFrame').src = response.url;
                    } else {
                        // If tracking code is not found, reload page to show error
                        window.location.href = "{{ route('search') }}?query=" + query;
                    }
                });
        });
    </script>


    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection
