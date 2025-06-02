<div class="modal fade" id="exampleModal">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <h3 class="modal-title w-100">New Transaction</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="submissionForm" method="POST" action="{{ route('documents.storeDoc') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                    
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                        </div>
                        <input type="text" class="form-control" id="fullName" name="full_name" value="{{ auth()->user()->fname }} {{ auth()->user()->lname }}" readonly required>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="route_id" name="route_id" value="0" hidden>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-building"></i></span>
                        </div>
                        <input type="text" class="form-control" id="office" name="department" value="{{ auth()->user()->department }}" readonly required>
                    </div>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-caret-down"></i></span>
                        </div>
                        <select class="form-select form-control" id="documentType" name="doc_type" required>
                            <option selected disabled value="">Choose Transaction...</option>
                            <option value="To President's Office">To President's Office</option>
                            <option value="To Other Offices">To Other Offices</option>
                        </select>
                    </div>

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                        </div>
                        <textarea class="form-control" id="subject" name="subject" rows="3" placeholder="Type your subject here..." required></textarea>
                    </div>
                    

                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-pen"></i></span>
                        </div>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" placeholder="Type your purpose here..." required></textarea>
                    </div>
                    
                    <div class="mb-2 row">
                        <div class="col-sm-8">
                            <input type="hidden" class="form-control" id="doc_stat" name="doc_stat" value="1" required readonly>
                        </div>
                    </div>
                    
              
                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-building"></i></span>
                    </div>
                    <select name="for_to[]" class="select2 form-select form-control" multiple="multiple" data-placeholder="Select Offices" style="width: 92%;">
                        @foreach($offices as $office)
                            <option value="{{ $office->office_name }}">{{ $office->office_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="custom-file">
                    <input type="file" class="custom-file-inputs" id="customFile" name="document" required>
                    <label class="custom-file-label" for="customFile">Choose file</label>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" form="submissionForm">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<script>
document.querySelector('.custom-file-inputs').addEventListener('change', function(e) {
var fileName = e.target.files[0].name;
e.target.nextElementSibling.innerHTML = fileName;
});
</script>