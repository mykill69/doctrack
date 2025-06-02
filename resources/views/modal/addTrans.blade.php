<div class="modal fade" id="exampleModalTrans">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <h3 class="modal-title w-100">Create Document Tracking Slip</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="submissionForm" method="POST" action="{{ route('storeDoctrack') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-list-ul"></i></span>
                        </div>
                        <select class="form-control" id="doc_type" name="doc_type" required>
                            <option value="">Document Type</option>
                            <option value="DPCR/IPCR">DPCR/IPCR</option>
                            <option value="Reimbursement">Reimbursement</option>
                            <option value="Travel Autority">Travel Autority</option>
                            <option value="Other Document">Other Document</option>
                        </select>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-envelope"></i>
                            </span>
                        </div>
                        <textarea class="form-control float-right" id="doc_title" name="doc_title" rows="2"
                            placeholder="Type the Document title here" required></textarea>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-user"></i>
                            </span>
                        </div>
                        <textarea class="form-control float-right" id="user_name" name="user_name" rows="2"
                            placeholder="Type your name or name in the Document" required></textarea>
                    </div>

                
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-file"></i>
                            </span>
                        </div>
                        <input type="file" class="form-control" id="customFile" name="file" accept="pdf/*" onchange="showFileName()">
                    </div>
                    <small id="filePlaceholder" class="form-text text-muted">
                        Attach a file if needed <i class="text-bold text-danger">(optional)</i>
                    </small>
                    

                    <div class="modal-footer col-sm-12">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function showFileName() {
        const input = document.getElementById('customFile');
        const placeholder = document.getElementById('filePlaceholder');
        if (input.files.length > 0) {
            placeholder.innerHTML = `<strong>Selected:</strong> ${input.files[0].name}`;
        } else {
            placeholder.innerHTML = `Attach a file if needed <i class="text-bold text-danger">(optional)</i>`;
        }
    }
</script>