<div class="ic wp">
  <p><a href="{{ admin_url('withdraw-pins') }}"><i class="fa fa-arrow-left"></i> All withdraw PINs</a> · <a href="{{ admin_url('users/' . $user->id) }}">Member profile</a> · <a href="{{ admin_url('withdraw-requests?tab=pending') }}">Withdraw requests</a></p>

  @if($generated)
    <div class="wp-reveal">
      <span>New PIN for {{ $user->name }}</span>
      <b>{{ $generated['pin'] }}</b>
      <small>{{ $generated['sms'] ? 'Also sent by SMS to ' . $user->phone_number . '.' : 'SMS failed - read this PIN to the member now.' }} It will not be shown again.</small>
    </div>
  @endif

  <div class="row">
    <div class="col-md-5">
      <div class="box box-primary"><div class="box-body">
        <h3 class="ic-h">{{ $user->name }}</h3>
        <p class="text-muted">{{ $user->phone_number }} · {{ $user->dtehm_member_id ?: ($user->business_name ?: 'no member ID') }}</p>
        <table class="table table-condensed ic-kv">
          <tr><td>PIN</td><td>@include('admin.withdraw-pins.badge', ['pin' => $pin])</td></tr>
          @if($pin)
            <tr><td>Last changed</td><td>{{ optional($pin->last_changed_at)->format('j M Y, H:i') ?? '-' }}</td></tr>
            <tr><td>Wrong tries</td><td>{{ $pin->failed_attempts }} of 5</td></tr>
            @if($pin->locked_until && $pin->locked_until->isFuture())
              <tr><td>Locked until</td><td class="text-danger"><b>{{ $pin->locked_until->format('H:i') }}</b> ({{ $pin->locked_until->diffForHumans() }})</td></tr>
            @endif
          @endif
          <tr><td>Balance</td><td>UGX {{ number_format($balance, 0) }}</td></tr>
          <tr><td>Pending withdrawals</td><td>{{ $pendingWithdrawals }}</td></tr>
        </table>

        @if($pin && $pin->locked_until && $pin->locked_until->isFuture())
          <form method="post" action="{{ admin_url('withdraw-pins/' . $user->id . '/unlock') }}">{{ csrf_field() }}
            <button class="btn btn-success btn-block btn-lg"><i class="fa fa-unlock"></i> Unlock now</button></form>
        @endif
      </div></div>

      <div class="box box-default"><div class="box-header with-border"><h3 class="box-title">History</h3></div>
        <div class="box-body no-padding"><table class="table table-condensed ic-table">
          @forelse($history as $l)
            <tr><td><b>{{ $l->label }}</b>@if($l->actor_type === 'admin') <small class="text-muted">by {{ optional($l->actor)->name ?? 'admin' }}</small>@endif
              @if(!is_null($l->sms_sent))<br><small class="{{ $l->sms_sent ? 'text-success' : 'text-danger' }}">{{ $l->sms_sent ? 'PIN sent by SMS' : 'SMS failed' }}</small>@endif
              @if($l->note)<br><small class="text-muted">"{{ $l->note }}"</small>@endif</td>
              <td class="text-right"><small class="text-muted">{{ optional($l->created_at)->format('j M Y H:i') }}</small></td></tr>
          @empty
            <tr><td class="text-center text-muted ic-empty">No history yet.</td></tr>
          @endforelse
        </table></div></div>
    </div>

    <div class="col-md-7">
      <div class="box box-success"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-magic"></i> Generate a new PIN and SMS it</h3></div>
        <div class="box-body">
          <p class="text-muted">Quickest and safest: a random PIN is created, sent to {{ $user->phone_number }} and shown to you once.</p>
          <form method="post" action="{{ admin_url('withdraw-pins/' . $user->id . '/generate') }}" data-confirm="Replace {{ $user->name }}'s PIN with a new random one and send it by SMS?">{{ csrf_field() }}
            <input type="text" name="note" class="form-control" maxlength="500" placeholder="Note (optional), e.g. member forgot PIN, called the office">
            <button class="btn btn-success" style="margin-top:8px"><i class="fa fa-paper-plane"></i> Generate &amp; send</button>
          </form>
        </div></div>

      <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-key"></i> Set a specific PIN</h3></div>
        <div class="box-body">
          <form method="post" action="{{ admin_url('withdraw-pins/' . $user->id . '/set') }}" id="wp-set-form" autocomplete="off">{{ csrf_field() }}
            <div class="row">
              <div class="col-sm-6"><label>New PIN</label><input type="password" name="pin" inputmode="numeric" pattern="\d{4}" maxlength="4" required class="form-control input-lg wp-pin" placeholder="4 digits"></div>
              <div class="col-sm-6"><label>Type it again</label><input type="password" name="pin_confirmation" inputmode="numeric" pattern="\d{4}" maxlength="4" required class="form-control input-lg wp-pin" placeholder="4 digits"></div>
            </div>
            <div class="wp-hint text-muted small" id="wp-hint">Use 4 digits. Easy PINs like 1234 or 0000 are not allowed.</div>
            <div class="checkbox"><label><input type="checkbox" name="send_sms" value="1" checked> Send the PIN to {{ $user->phone_number }} by SMS</label></div>
            <input type="text" name="note" class="form-control" maxlength="500" placeholder="Note (optional)">
            <button class="btn btn-primary" style="margin-top:8px"><i class="fa fa-check"></i> Set PIN</button>
          </form>
        </div></div>

      @if($pin)
        <div class="box box-default"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-refresh"></i> Let the member choose a new PIN</h3></div>
          <div class="box-body">
            <p class="text-muted">Removes the current PIN. The app will ask {{ $user->name }} to create a new one before the next withdrawal (an SMS tells them).</p>
            <form method="post" action="{{ admin_url('withdraw-pins/' . $user->id . '/remove') }}" data-confirm="Remove {{ $user->name }}'s PIN? They must create a new one in the app.">{{ csrf_field() }}
              <input type="text" name="note" class="form-control" maxlength="500" placeholder="Note (optional)">
              <button class="btn btn-warning" style="margin-top:8px"><i class="fa fa-eraser"></i> Remove PIN</button>
            </form>
          </div></div>
      @endif
    </div>
  </div>
</div>
