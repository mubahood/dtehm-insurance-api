{{-- Shared "Record payment" dialog: money is applied to the oldest unpaid months first --}}
<div class="modal fade" id="ic-pay-modal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="ic-pay-form">
      {{ csrf_field() }}
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Record payment – <span id="ic-pay-name"></span></h4></div>
      <div class="modal-body">
        <p class="text-muted">The money is applied to the oldest unpaid months first (late fees included).</p>
        <div class="form-group">
          <label>Amount received (UGX)</label>
          <input type="number" min="1" step="1" name="amount" id="ic-pay-amount" class="form-control input-lg" required>
          <div class="ic-presets" id="ic-pay-presets"></div>
        </div>
        <div class="form-group">
          <label>How was it paid?</label>
          <select name="method" class="form-control">
            @foreach(['Cash', 'Mobile Money', 'Bank Transfer', 'Cheque', 'Other'] as $m)<option>{{ $m }}</option>@endforeach
          </select>
        </div>
        <div class="form-group"><label>Receipt / reference (optional)</label><input type="text" name="reference" class="form-control" maxlength="255"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button class="btn btn-success" id="ic-pay-submit">Record payment</button></div>
    </form>
  </div>
</div>
