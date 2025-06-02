<!-- Modal for re-routing the document -->
<div class="modal fade" id="reRouteModal_{{ $slip->rslip_id }}" tabindex="-1" role="dialog" aria-labelledby="reRouteModalLabel_{{ $slip->rslip_id }}" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reRouteModalLabel_{{ $slip->rslip_id }}">Re-route Document</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="submissionForm" method="POST" action="{{ route('viewPdfRoute') }}" enctype="multipart/form-data">
                    @csrf
                    <!-- Add your form fields here -->
                    <div class="form-group">
                        <label for="exampleInput">Example Input</label>
                        <input type="text" name="exampleInput" class="form-control" id="exampleInput">
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" form="submissionForm">Submit</button>
            </div>
        </div>
    </div>
</div>