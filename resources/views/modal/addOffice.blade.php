<div class="modal fade" id="addOfficeModal" tabindex="-1" role="dialog" aria-labelledby="addOfficeLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="addOfficeForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOfficeLabel">Add Office</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="form-group">
                        <label for="office_name">Office Name</label>
                        <input type="text" class="form-control" name="office_name" required>
                    </div>
                    <div class="form-group">
                        <label for="office_abbr">Office Abbreviation</label>
                        <input type="text" class="form-control" name="office_abbr" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Office</button>
                </div>
            </div>
        </form>
    </div>
</div>
