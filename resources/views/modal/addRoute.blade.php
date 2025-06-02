@if($documents->isNotEmpty())
@foreach($documents as $document)
<!-- Modal -->
<div class="modal fade" id="exampleModal1" {{ $document->id }}>
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <button type="button" class="close text-right" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
            
            <div class="modal-body">
                <form id="submissionForm" method="POST" action="{{ route('documents.update', $document->id) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="route_id" value="{{ request()->input('route_id') }}">
                    <input type="hidden" name="user_id" value="{{ $document->user_id }}">
                    <input type="hidden" name="new_user" value="{{ auth()->user()->id }}">
                    <input type="hidden" name="redirectUrl" value="{{ request()->input('redirectUrl') }}">
                    <div class="mb-3 row">
                        <label for="purpose" class="col-sm-4 col-form-label">Comments (optional)</label>
                        <div class="col-sm-12">
                            <textarea class="form-control" id="comments" name="comments" rows="3" placeholder="Add your comments here..."></textarea>
                        </div>
                        <input type="hidden" name="status_update" value="3">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" form="submissionForm">Acknowledge</button>
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