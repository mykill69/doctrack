<div class="modal fade" id="exampleModalUser">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <h3 class="modal-title w-100">Add New User</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form id="submissionForm" method="POST" action="{{ route('users.addUser') }}" enctype="multipart/form-data">
                    @csrf
                    

                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-user"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-lock"></i>
                                    </span>
                                </div>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fa fa-user"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control" id="fname" name="fname" placeholder="First name" required>
                            </div>
                        </div>
                    
                        <div class="col-md-4">
                            <div class="input-group mb-2">
                                
                                <input type="text" class="form-control" id="mname" name="mname" placeholder="Middle name" required>
                            </div>
                        </div>
                    
                        <div class="col-md-4">
                            <div class="input-group mb-2">
                                
                                <input type="text" class="form-control" id="lname" name="lname" placeholder="Last name" required>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-building"></i></span>
                        </div>
                            <select class="form-control" id="department" name="department" data-placeholder="Select Offices">
                                <option value="" disabled selected>Select from Offices</option>
                                @foreach($offices as $office)
                                <option value="{{ $office->office_name }}">{{ $office->office_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-list-ul"></i></span>
                        </div>
                            <select class="form-control" id="role" name="role" data-placeholder="Select Role">
                                <option value="" disabled selected>Select Role</option>
                                <option value="Administrator">Administrator</option>
                                <option value="super_user">Super User</option>
                                <option value="records_officer">Records Officer</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<script>
document.getElementById('password').addEventListener('input', function () {
const password = this.value;
const errorElement = document.getElementById('passwordError');
if (password.length < 8) {
errorElement.style.display = 'block'; // Show error message
} else {
errorElement.style.display = 'none'; // Hide error message
}
});
</script>