@if($documents->isNotEmpty())
@foreach($documents as $document)
<!-- Modal -->
<div class="modal fade" id="reRouteModal" {{ $document->id }}>
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <h3 class="modal-title w-100">Re-route Slip Form</h3>
                <button type="button" class="close text-right" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="submissionForm" method="POST" action="{{ route('updateAssign', $document->route_id) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="route_id" value="{{ request()->input('route_id') }}">
                    <input type="hidden" name="user_id" value="{{ $document->user_id }}">
                    <input type="hidden" name="new_user" value="{{ auth()->user()->id }}">
                    <input type="hidden" name="redirectUrl" value="{{ request()->input('redirectUrl') }}">
                    
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-users"></i>
                            </span>
                        </div>
                        <textarea class="form-control float-right" id="assigned_to" name="assigned_to" rows="3" placeholder="Type the name of the Personnel/s to be assigned here" required></textarea>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-comment"></i>
                            </span>
                        </div>
                        <textarea class="form-control float-right" id="assign_com" name="assign_com" rows="3" placeholder="Write your comments here" required></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
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