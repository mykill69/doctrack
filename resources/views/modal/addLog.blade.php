<div class="modal fade" id="addLogModal">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <h3 class="modal-title w-100">{{ $documentTrack->docslip_id }}</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="submissionForm" method="POST" action="{{ route('storeDoctrackUpdate') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id"
                        value="{{ isset($documentTrack) ? $documentTrack->user_id : '' }}"><br>
                    <input type="hidden" name="update_by" value="{{ auth()->user()->id }}">
                    <input type="hidden" name="docslip_id"
                        value="{{ isset($documentTrack) ? $documentTrack->docslip_id : '' }}">
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-list-ul"></i></span>
                        </div>
                        <input type="text" name="doc_type" class="form-control"
                            value="{{ isset($documentTrack) ? $documentTrack->doc_type : '' }}" readonly>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-envelope"></i>
                            </span>
                        </div>
                        <input type="text" name="doc_title" class="form-control"
                            value="{{ isset($documentTrack) ? $documentTrack->doc_title : '' }}" readonly>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-user"></i>
                            </span>
                        </div>
                        <select name="update_by" id="update_by" class="form-control" required>
                            <option value="" selected disabled hidden>— Select an Individual User —</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->fname }} {{ $user->lname }}</option>
                            @endforeach
                        </select>
                    </div>



                    {{-- <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-user"></i>
                            </span>
                        </div> --}}
                    <input type="hidden" name="user_name" class="form-control"
                        value="{{ isset($documentTrack) ? $documentTrack->user_name : '' }}" readonly>
                    {{-- </div> --}}
                    <div class="input-group mb-2">
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="customFile">
                            <label class="custom-file-label" for="customFile" id="fileLabel">
                                Attach a file if needed <i class="text-bold text-danger">(optional)</i>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer col-sm-12">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('customFile');
        const fileLabel = document.getElementById('fileLabel');

        fileInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'Attach a file if needed';
            fileLabel.textContent = fileName;
        });
    });
</script>
