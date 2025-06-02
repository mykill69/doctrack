<!-- QR Scanner Modal -->
<div class="modal fade" id="scannerModal" tabindex="-1" role="dialog" aria-labelledby="scannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content p-3">
        <div class="modal-header">
          <h5 class="modal-title" id="scannerModalLabel">Scan QR Code</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="stopScanner()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body text-center">
          <div id="reader" style="width: 100%; max-width: 300px; margin: auto;"></div>
        </div>
      </div>
    </div>
  </div>