@extends('layouts.main')

@php
    $title = 'Archive Logbook';
@endphp

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-archive mr-2"></i>ARCHIVE LOGBOOK</h3>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    Archive previous years of transactions. Archiving only marks records as archived;
                                    all data, attachments and history are preserved and remain available in the
                                    Archived History Logbook.
                                </p>

                                @if (empty($years))
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        There are no previous years eligible for archiving yet. Transactions from the
                                        current year and any year that has already been archived are not listed.
                                    </div>
                                @else
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="archiveYear">Previous Year</label>
                                                <select id="archiveYear" class="form-control">
                                                    @foreach ($years as $year)
                                                        <option value="{{ $year }}" {{ (string) $year === (string) $defaultYear ? 'selected' : '' }}>
                                                            {{ $year }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="archiveType">Transaction Type</label>
                                                <select id="archiveType" class="form-control">
                                                    <option value="both">Both</option>
                                                    <option value="routing_slip">Routing Slip</option>
                                                    <option value="document_tracking">Document Tracking</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-group w-100">
                                                <button type="button" id="previewBtn" class="btn btn-outline-success">
                                                    <i class="fas fa-search mr-1"></i> Preview Records
                                                </button>
                                                <button type="button" id="archiveBtn" class="btn btn-success ml-2" disabled>
                                                    <i class="fas fa-archive mr-1"></i> Archive Selected Year
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="previewPanel" class="mt-2" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-box bg-light">
                                                    <span class="info-box-icon bg-success"><i class="fas fa-route"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Routing Slips</span>
                                                        <span class="info-box-number" id="previewRouting">0</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-box bg-light">
                                                    <span class="info-box-icon bg-info"><i class="fas fa-file-alt"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Document Tracking</span>
                                                        <span class="info-box-number" id="previewDocument">0</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-box bg-light">
                                                    <span class="info-box-icon bg-warning"><i class="fas fa-layer-group"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Eligible Transactions</span>
                                                        <span class="info-box-number" id="previewTotal">0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-outline card-secondary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-list mr-2"></i>RECENT ARCHIVE OPERATIONS</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-sm" style="font-size: 0.85rem;">
                                        <thead>
                                            <tr>
                                                <th>YEAR</th>
                                                <th>TRANSACTION TYPE</th>
                                                <th>RECORDS</th>
                                                <th>ARCHIVED BY</th>
                                                <th>DATE ARCHIVED</th>
                                                <th>STATUS</th>
                                                <th>MESSAGE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentOperations as $operation)
                                                <tr>
                                                    <td>{{ $operation->archive_year }}</td>
                                                    <td>
                                                        @if ($operation->transaction_type === 'routing_slip')
                                                            Routing Slip
                                                        @elseif ($operation->transaction_type === 'document_tracking')
                                                            Document Tracking
                                                        @else
                                                            {{ ucwords(str_replace('_', ' ', $operation->transaction_type)) }}
                                                        @endif
                                                    </td>
                                                    <td>{{ number_format($operation->records_count) }}</td>
                                                    <td>
                                                        {{ $operation->archivedBy ? trim($operation->archivedBy->fname . ' ' . $operation->archivedBy->lname) : 'N/A' }}
                                                    </td>
                                                    <td>{{ $operation->archived_at ? \Carbon\Carbon::parse($operation->archived_at)->format('M d, Y h:i A') : 'N/A' }}</td>
                                                    <td>
                                                        @if ($operation->status === 'completed')
                                                            <span class="badge badge-success">Completed</span>
                                                        @elseif ($operation->status === 'skipped')
                                                            <span class="badge badge-secondary">Skipped</span>
                                                        @elseif ($operation->status === 'failed')
                                                            <span class="badge badge-danger">Failed</span>
                                                        @else
                                                            <span class="badge badge-warning">Processing</span>
                                                        @endif
                                                    </td>
                                                    <td><small class="text-muted">{{ $operation->message ?: '—' }}</small></td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No archive operations yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script>
        $(function() {
            var previewData = null;
            var previewUrl = "{{ route('archive.preview') }}";
            var archiveUrl = "{{ route('archive.store') }}";
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            var currentType = $('#archiveType').val();

            function resetPreview() {
                previewData = null;
                $('#previewPanel').hide();
                $('#archiveBtn').prop('disabled', true);
            }

            function countForType(type) {
                if (!previewData) {
                    return 0;
                }

                if (type === 'routing_slip') {
                    return previewData.routing_slip;
                }

                if (type === 'document_tracking') {
                    return previewData.document_tracking;
                }

                return previewData.total;
            }

            function refreshButtonState() {
                var count = countForType($('#archiveType').val());
                $('#archiveBtn').prop('disabled', !previewData || count <= 0);
            }

            function loadPreview() {
                var year = $('#archiveYear').val();

                if (!year) {
                    return;
                }

                $('#previewBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Loading...');

                $.ajax({
                    url: previewUrl,
                    type: 'GET',
                    data: { year: year },
                    dataType: 'json'
                }).done(function(response) {
                    previewData = response.counts;
                    $('#previewRouting').text(response.counts.routing_slip.toLocaleString());
                    $('#previewDocument').text(response.counts.document_tracking.toLocaleString());
                    $('#previewTotal').text(response.counts.total.toLocaleString());
                    $('#previewPanel').show();
                    refreshButtonState();
                }).fail(function(xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to preview the selected year.';
                    Swal.fire('Error', message, 'error');
                }).always(function() {
                    $('#previewBtn').prop('disabled', false).html('<i class="fas fa-search mr-1"></i> Preview Records');
                });
            }

            $('#previewBtn').on('click', loadPreview);

            $('#archiveYear').on('change', function() {
                resetPreview();
                loadPreview();
            });

            $('#archiveType').on('change', function() {
                refreshButtonState();
            });

            $('#archiveBtn').on('click', function() {
                var year = $('#archiveYear').val();
                var type = $('#archiveType').val();
                var count = countForType(type);
                var typeLabel = $('#archiveType option:selected').text();

                if (!year || count <= 0) {
                    return;
                }

                Swal.fire({
                    title: 'Archive ' + year + '?',
                    html: 'You are about to archive <strong>' + count.toLocaleString() +
                        '</strong> ' + typeLabel + ' transaction(s) from <strong>' + year + '</strong>.' +
                        '<br><br><span class="text-muted">Records will only be marked as archived. All data, ' +
                        'files and history remain available in the Archived History Logbook.</span>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, archive records',
                    confirmButtonColor: '#28a745',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $('#archiveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Archiving...');

                    $.ajax({
                        url: archiveUrl,
                        type: 'POST',
                        data: {
                            _token: csrfToken,
                            year: year,
                            type: type
                        },
                        dataType: 'json'
                    }).done(function(response) {
                        Swal.fire({
                            title: response.status === 'completed' ? 'Archive Completed' : 'Nothing to Archive',
                            text: response.message,
                            icon: response.status === 'completed' ? 'success' : 'info'
                        }).then(function() {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'The archive operation failed.';
                        Swal.fire('Error', message, 'error');
                    }).always(function() {
                        $('#archiveBtn').prop('disabled', false).html('<i class="fas fa-archive mr-1"></i> Archive Selected Year');
                        refreshButtonState();
                    });
                });
            });

            if ($('#archiveYear').length) {
                loadPreview();
            }
        });
    </script>
@endpush
