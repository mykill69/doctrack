<div class="modal fade" id="exampleModal2">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Document</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="submissionForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-3 row">
                        <label for="docNumber" class="col-sm-4 col-form-label">Document Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="docNumber" name="doc_number" readonly>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="subject" class="col-sm-4 col-form-label">Subject</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" id="subject" name="subject" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="documentType" class="col-sm-4 col-form-label">Transaction Type</label>
                        <div class="col-sm-8">
                            <select class="form-select form-control" id="documentType" name="doc_type" required>
                                <option value="Incoming">Internal Incoming</option>
                                <option value="Outgoing">Internal Outgoing</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="purpose" class="col-sm-4 col-form-label">Purpose</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" id="purpose" name="purpose" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>