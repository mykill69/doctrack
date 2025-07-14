<div class="modal fade" id="routslip">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <h3 class="modal-title w-100">Create Routing Slip</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="routingSlipForm" method="POST" action="{{ route('storeSlip') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                    <div class="mb-2 row">

                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fa fa-hashtag"></i>
                                </span>
                            </div>
                            <input type="number" class="form-control float-right" id="ctrl_no" name="ctrl_no"
                                placeholder="Control number" required>
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="far fa-calendar-alt"></i>
                                </span>
                            </div>
                            <input type="date" class="form-control float-right" id="date_received"
                                name="date_received" required>
                        </div>

                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fa fa-flag"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control float-right" id="source" name="source"
                                placeholder="Type the source here" required>
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fa fa-envelope"></i>
                                </span>
                            </div>
                            <textarea class="form-control float-right" id="subject" name="subject" rows="2"
                                placeholder="Type the subject here" required></textarea>
                        </div>

                        @php
                            $isSuperUser = auth()->user()->role === 'super_user';
                        @endphp

                        @if ($isSuperUser)
                            <!-- Select Remarks (Visible only to super_user) -->
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-list-ul"></i></span>
                                </div>
                                <select class="form-control" id="transRemarks" name="trans_remarks" required>
                                    <option value="">Select Remarks</option>
                                    <option value="Appropriate Action">Appropriate Action</option>
                                    <option value="Comment &/or Recommendation">Comment &/or Recommendation</option>
                                    <option value="Information">Information</option>
                                    <option value="Endorsement">Endorsement</option>
                                    <option value="Edit/Correct">Edit/Correct</option>
                                    <option value="Review/Study">Review/Study</option>
                                    <option value="File">File</option>
                                    <option value="Draft Reply">Draft Reply</option>
                                </select>
                            </div>

                            <!-- Destination (Visible only to super_user) -->
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-map-marker"></i></span>
                                </div>
                                <input type="text" class="form-control float-right" id="destination"
                                    name="r_destination" placeholder="Enter destination..." required>
                            </div>
                        @endif


                        <div class="input-group mb-3">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="chooseDocuments" name="document"
                                    required>
                                <label class="custom-file-label" for="chooseDocuments">Choose documents</label>
                            </div>
                        </div>
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fa fa-user"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control float-right" id="received_name"
                                name="received_name" placeholder="Name on the Received stamp" required>
                        </div>
                        <!-- Upload File Stamp -->
                        {{-- <div class="input-group mb-3">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="uploadStamp" name="file_stamp"
                                    required>
                                <label class="custom-file-label" for="uploadStamp">Upload file stamp</label>
                            </div>
                        </div> --}}




                        <input type="hidden" id="route_status" name="route_status" value="1" required>
                        <div class="modal-footer col-sm-12">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Automatically update the label when a file is selected
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || "Choose file";
            e.target.nextElementSibling.innerText = fileName;
        });
    });
</script>
