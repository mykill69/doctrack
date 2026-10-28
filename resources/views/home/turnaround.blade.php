@extends('layouts.main')

<style>
    .dataTables_processing {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        font-weight: bold;
        z-index: 1000;
    }

    .turnaround-tabs .nav-link {
        color: #495057;
        font-weight: 600;
    }

    .turnaround-tabs .nav-link.active {
        color: #007bff;
    }

    .turnaround-summary .badge {
        font-size: 0.8rem;
        font-weight: 600;
    }

    #turnaroundReport.loading {
        opacity: 0.55;
        pointer-events: none;
    }
</style>

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">

                {{-- Type and date filters --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-clock mr-1"></i> TURNAROUND TIME REPORT
                                    <span class="text-primary">
                                        — <span id="turnaroundTypeLabel">{{ strtoupper($typeLabel) }}</span>
                                    </span>
                                </h3>
                                <div class="card-tools">
                                    <form id="turnaroundFilterForm" class="form-inline flex-wrap"
                                        onsubmit="return false;">
                                        <label class="mr-1 mb-0" for="type">Transaction Type</label>
                                        <select id="type" class="form-control form-control-sm mr-2 mb-1">
                                            <option value="routing"
                                                {{ $type === 'routing' ? 'selected' : '' }}>
                                                Routing Slips
                                            </option>
                                            <option value="tracking"
                                                {{ $type === 'tracking' ? 'selected' : '' }}>
                                                Tracking Documents
                                            </option>
                                        </select>

                                        <label class="mr-1 mb-0" for="start_date">From</label>
                                        <input type="date" id="start_date"
                                            class="form-control form-control-sm mr-2 mb-1"
                                            value="{{ $startDate }}">

                                        <label class="mr-1 mb-0" for="end_date">To</label>
                                        <input type="date" id="end_date"
                                            class="form-control form-control-sm mr-2 mb-1"
                                            value="{{ $endDate }}">

                                        <button type="submit" class="btn btn-primary btn-sm mr-1 mb-1">
                                            <i class="fas fa-filter mr-1"></i> Filter
                                        </button>
                                        <button type="button" id="turnaroundResetBtn"
                                            class="btn btn-default btn-sm mb-1">
                                            <i class="fas fa-redo mr-1"></i> Reset
                                        </button>
                                        <span id="turnaroundLoading" class="text-muted ml-2 mb-1"
                                            style="display: none;">
                                            <i class="fas fa-spinner fa-spin mr-1"></i> Loading...
                                        </span>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="turnaroundReport">
                    @include('home.partials.turnaround_report')
                </div>

            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script>
        $(document).ready(function() {
            const reportContainer = document.getElementById('turnaroundReport');
            const loadingIndicator = document.getElementById('turnaroundLoading');
            let requestSequence = 0;

            function initTurnaroundTable() {
                if (!document.getElementById('turnaroundTable')) {
                    return;
                }

                if ($.fn.DataTable.isDataTable('#turnaroundTable')) {
                    $('#turnaroundTable').DataTable().destroy();
                }

                $('#turnaroundTable').DataTable({
                    "responsive": true,
                    "pageLength": 25,
                    "deferRender": true,
                    "lengthMenu": [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    "order": [
                        [3, "desc"]
                    ],
                    "language": {
                        "processing": '<i class="fas fa-spinner fa-spin"></i> Loading...',
                        "search": "Search:",
                        "searchPlaceholder": "Search transactions...",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "emptyTable": "No completed transactions found",
                        "zeroRecords": "No matching transactions found"
                    }
                });
            }

            function loadTurnaround() {
                const type = document.getElementById('type').value;
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                const requestId = ++requestSequence;

                const params = new URLSearchParams();
                params.set('type', type);

                if (startDate !== '') {
                    params.set('start_date', startDate);
                }

                if (endDate !== '') {
                    params.set('end_date', endDate);
                }

                reportContainer.classList.add('loading');
                loadingIndicator.style.display = 'inline-block';

                fetch("{{ route('turnaround.data') }}?" + params.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Unable to load the turnaround report.');
                        }

                        return response.json();
                    })
                    .then(function(data) {
                        if (requestId !== requestSequence) {
                            return;
                        }

                        if ($.fn.DataTable.isDataTable('#turnaroundTable')) {
                            $('#turnaroundTable').DataTable().destroy();
                        }

                        reportContainer.innerHTML = data.html;
                        document.getElementById('turnaroundTypeLabel').textContent = data.typeLabel;

                        initTurnaroundTable();
                    })
                    .catch(function(error) {
                        if (requestId !== requestSequence) {
                            return;
                        }

                        alert(error.message);
                    })
                    .finally(function() {
                        if (requestId !== requestSequence) {
                            return;
                        }

                        reportContainer.classList.remove('loading');
                        loadingIndicator.style.display = 'none';
                    });
            }

            document.getElementById('turnaroundFilterForm').addEventListener('submit', function(event) {
                event.preventDefault();
                loadTurnaround();
            });

            ['type', 'start_date', 'end_date'].forEach(function(id) {
                document.getElementById(id).addEventListener('change', loadTurnaround);
            });

            document.getElementById('turnaroundResetBtn').addEventListener('click', function() {
                document.getElementById('type').value = 'routing';
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
                loadTurnaround();
            });

            initTurnaroundTable();
        });
    </script>
@endpush
