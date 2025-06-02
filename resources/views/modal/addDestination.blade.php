
    @if($routingSlips->isNotEmpty())
     @foreach($routingSlips as $slip) 
    <!-- Modal -->
<div class="modal fade" id="exampleModal1" {{ $slip->id }}>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <h3 class="modal-title w-100">Create New Transaction</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="submissionForm" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                    <div class="mb-3 row">
                        <label for="documentType" class="col-sm-4 col-form-label">Transaction Type</label>
                        <div class="col-sm-8">
                            <select class="form-select form-control" id="documentType" name="doc_type" required>
                                <option selected disabled value="">Choose Transaction...</option>
                                <option value="Incoming">External Documents </option>
                                <option value="Outgoing">Internal Documents </option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="purpose" class="col-sm-4 col-form-label">Routing Slip Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="document" name="document" value="{{ isset($slip) ? $slip->rslip_id : '' }}" required readonly>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="purpose" class="col-sm-4 col-form-label">Document Name</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="document" value="{{ isset($slip) ? $slip->rslip_id : '' }}" readonly required>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="purpose" class="col-sm-4 col-form-label">Subject</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="subject" name="subject" rows="2" placeholder="Type your purpose here..." value="{{ isset($slip) ? $slip->subject : '' }}" required readonly>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="purpose" class="col-sm-4 col-form-label">Purpose</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" id="purpose" name="purpose" rows="2" placeholder="Type your purpose here..." required></textarea>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="purpose" class="col-sm-4 col-form-label">This Document is For/To</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="subject" name="subject" rows="2" placeholder="Type your subject here..." value="{{ isset($slip) ? $slip->r_destination : '' }}" required readonly>
                        </div>
                    </div>
                    <input type="hidden" id="route_status" name="route_status" value="3" required>
                    <div class="mb-3 row">
                        <label for="toDepartment" class="col-sm-4 col-form-label">Destination/Department</label>
                        <div class="col-sm-8">
                            <select name="destination[]" class="select2" multiple="multiple" data-placeholder="Select Offices" style="width: 100%;">
                                @foreach($offices as $office)
                                <option value="{{ $office->office_name }}"
                                    {{ isset($document) && in_array($office->office_name, explode(',', $document->destination)) ? 'selected' : '' }}>
                                    {{ $office->office_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" form="submissionForm">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
@else
<p>No documents found matching the search query or you do not have access to this document.</p>
@endif