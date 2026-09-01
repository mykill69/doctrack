@extends('layouts.main')

<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff !important;
        border-color: #187744 !important;
        color: #fff;
        padding: 0 10px;
        margin-top: 0.31rem;
    }
    .dataTables_processing {
        background: rgba(255,255,255,0.9);
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        font-weight: bold;
        z-index: 1000;
    }
    .doctrack-input {
        transition: all 0.3s ease;
    }
    .doctrack-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
</style>

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">DOCUMENT TRACKING SLIP</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem; width: 100%;">
                                        <thead>
                                            <tr>
                                                @if (auth()->user()->id == 1235)
                                                    <th>CTRL #</th>
                                                @endif
                                                <th>DATE RECEIVED</th>
                                                <th>SOURCE</th>
                                                <th>SUBJECT MATTER</th>
                                                <th>ACTION UNIT</th>
                                                <th>RECEIVED BY/DATE</th>
                                                <th>ACTION TAKEN</th>
                                                <th>DATE RELEASED</th>
                                                <th>REMARKS</th>
                                                <th>STATUS</th>
                                                <th>TRACKING CODE</th>
                                                <th>TOTAL DURATION</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection

@push('page_scripts')
<script>
    $(document).ready(function() {
        var columns = [
            { "data": "date_received", "name": "created_at", "orderable": true },
            { "data": "source", "name": "user_name", "orderable": true },
            { "data": "subject", "name": "doc_title", "orderable": true },
            { "data": "action_unit", "orderable": false },
            { "data": "received_by_date", "orderable": false },
            { "data": "action_taken", "orderable": false },
            { "data": "date_released", "name": "updated_at", "orderable": true },
            { "data": "remarks", "orderable": false },
            { "data": "status", "name": "doctrack_stat", "orderable": true },
            { "data": "tracking_code", "orderable": false },
            { "data": "duration", "orderable": true },
            { "data": "action", "orderable": false, "searchable": false }
        ];

        @if (auth()->user()->id == 1235)
            columns.unshift({ 
                "data": "ctrl_input", 
                "name": "ctrl_no", 
                "orderable": true,
                "searchable": true 
            });
        @endif

        var table = $('#example1').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('doctrackSlip.data') }}",
                "type": "GET",
                "data": function(d) {
                    // You can add additional parameters here if needed
                    return d;
                }
            },
            "columns": columns,
            "order": [[1, "desc"]], // Default order by date_received (index 1)
            "pageLength": 50,
            "deferRender": true,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "language": {
                "processing": '<i class="fas fa-spinner fa-spin"></i> Loading...',
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoFiltered": "(filtered from _MAX_ total entries)"
            },
            "drawCallback": function() {
                attachInlineEditHandlers();
            }
        });

        // Initial attachment
        attachInlineEditHandlers();

        function attachInlineEditHandlers() {
            // Remove existing handlers to prevent duplicates
            $('.doctrack-input').off('focus blur keypress');
            
            // Store original value on focus
            $('.doctrack-input').on('focus', function() {
                $(this).data('original-value', $(this).val());
            });
            
            // Handle blur event (save on losing focus)
            $('.doctrack-input').on('blur', function() {
                saveInputValue($(this));
            });
            
            // Handle Enter key press
            $('.doctrack-input').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    $(this).blur(); // Trigger blur to save
                }
            });
        }

        function saveInputValue($input) {
            var doctrackId = $input.data('id');
            var field = $input.data('field');
            var value = $input.val().trim();
            var originalValue = $input.data('original-value');
            
            // Don't submit if value hasn't changed
            if (value === originalValue) {
                return;
            }
            
            // Get CSRF token from meta tag
            var token = $('meta[name="csrf-token"]').attr('content');
            
            // Show loading state
            $input.prop('disabled', true);
            $input.css('opacity', '0.6');
            
            // Make AJAX request with proper CSRF
            $.ajax({
                url: "{{ url('/doctrack-slip') }}/" + doctrackId,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token
                },
                data: {
                    _method: 'PUT',
                    [field]: value
                },
                success: function(response) {
                    console.log('Update successful:', response);
                    
                    // Update stored original value
                    $input.data('original-value', value);
                    
                    // Show success indicator
                    $input.css('border', '2px solid #28a745');
                    
                    // Show toast notification
                    toastr.success('Updated successfully');
                    
                    // Remove success indicator after delay
                    setTimeout(function() {
                        $input.css('border', '');
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    console.error('Update failed:', xhr.responseText);
                    
                    // Revert to original value
                    $input.val(originalValue);
                    
                    // Show error indicator
                    $input.css('border', '2px solid #dc3545');
                    
                    // Show error toast
                    toastr.error('Failed to update. Please try again.');
                    
                    // Remove error indicator after delay
                    setTimeout(function() {
                        $input.css('border', '');
                    }, 3000);
                },
                complete: function() {
                    // Re-enable input
                    $input.prop('disabled', false);
                    $input.css('opacity', '1');
                }
            });
        }
    });
</script>
@endpush