@php($ugx = fn($v) => 'UGX ' . number_format((float) $v, 0))
@php($chip = ['overdue' => 'danger', 'due' => 'warning', 'up_to_date' => 'success', 'completed' => 'success', 'suspended' => 'default', 'cancelled' => 'default', 'expired' => 'default'])
@php($mchip = ['paid' => 'success', 'overdue' => 'danger', 'due' => 'warning', 'partial' => 'info', 'waived' => 'default', 'upcoming' => 'default'])
<div class="ic">
  <p><a href="{{ admin_url('insurance-center') }}"><i class="fa fa-arrow-left"></i> Back to Control Center</a></p>
  <div class="row">
    <div class="col-md-4">
      <div class="box box-primary">
        <div class="box-body">
          <h3 class="ic-h">{{ optional($policy->user)->name }} <span class="label label-{{ $chip[$s['state']] ?? 'default' }}">{{ $s['state_label'] }}</span></h3>
          <p class="text-muted">{{ optional($policy->user)->phone_number }} · {{ optional($policy->user)->dtehm_member_id }}<br>{{ $s['program_name'] }} · policy {{ $s['policy_number'] }}</p>
          <table class="table table-condensed ic-kv">
            <tr><td>Covered</td><td>{{ $s['start_date'] }} → {{ $s['end_date'] }}</td></tr>
            <tr><td>Months</td><td>{{ $s['first_month'] }} – {{ $s['last_month'] }} ({{ $s['months_total'] }})</td></tr>
            <tr><td>Paid</td><td>{{ $s['months_paid'] }} months · {{ $ugx($s['paid_amount']) }}</td></tr>
            <tr><td>Due now</td><td><b class="{{ $s['due_now_amount'] > 0 ? 'text-danger' : '' }}">{{ $ugx($s['due_now_amount']) }}</b> @if($s['late_fees'] > 0)<small>(incl. {{ $ugx($s['late_fees']) }} fees)</small>@endif</td></tr>
            <tr><td>Still to pay</td><td><b>{{ $ugx($s['balance']) }}</b></td></tr>
          </table>
          @if($deletedMonths->count())
            <div class="alert alert-warning">Deleted months: {{ $deletedMonths->implode(', ') }}. Use <em>Fix those problems</em> on the Control Center if they should be restored.</div>
          @endif
          @if($s['can_pay'])
            <button type="button" class="btn btn-success btn-block btn-lg ic-pay-btn" data-url="{{ admin_url('insurance-center/policy/' . $policy->id . '/pay') }}" data-name="{{ optional($policy->user)->name }}" data-due="{{ (int) $s['due_now_amount'] }}" data-next="{{ (int) $s['next_due_amount'] }}" data-balance="{{ (int) $s['balance'] }}"><i class="fa fa-money"></i> Record payment</button>
          @endif
          <hr>
          <form method="post" action="{{ admin_url('insurance-center/policy/' . $policy->id . '/status') }}" class="ic-status" data-confirm="Change the status of this policy?">
            {{ csrf_field() }}
            <input type="text" name="reason" class="form-control input-sm" placeholder="Reason (for suspend / cancel)">
            <div class="btn-group btn-group-justified" style="margin-top:6px">
              @if($policy->status === 'Active')<div class="btn-group"><button name="action" value="suspend" class="btn btn-sm btn-warning">Suspend</button></div>@endif
              @if($policy->status === 'Suspended')<div class="btn-group"><button name="action" value="activate" class="btn btn-sm btn-success">Re-activate</button></div>@endif
              @if(in_array($policy->status, ['Active', 'Suspended']))<div class="btn-group"><button name="action" value="cancel" class="btn btn-sm btn-danger">Cancel policy</button></div>@endif
            </div>
          </form>
          <p class="text-muted small" style="margin-top:8px"><a href="{{ admin_url('insurance-subscriptions/' . $policy->id) }}">Raw record</a> · <a href="{{ admin_url('users/' . $policy->user_id) }}">Member profile</a></p>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Months</h3></div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover ic-table">
            <thead><tr><th>Month</th><th>Due</th><th class="text-right">Premium</th><th class="text-right">Late fee</th><th class="text-right">Paid</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($s['months'] as $m)
              <tr class="{{ $m['status'] === 'overdue' ? 'danger' : '' }}">
                <td><b>{{ $m['label'] }}</b></td>
                <td>{{ $m['due_date'] }}</td>
                <td class="text-right">{{ $ugx($m['amount']) }}</td>
                <td class="text-right">{{ $m['late_fee'] > 0 ? $ugx($m['late_fee']) : '–' }}</td>
                <td class="text-right">{{ $m['paid'] > 0 ? $ugx($m['paid']) : '–' }}@if($m['paid_on'])<br><small class="text-muted">{{ $m['paid_on'] }}</small>@endif</td>
                <td><span class="label label-{{ $mchip[$m['status']] ?? 'default' }}">{{ ucfirst(str_replace('_', ' ', $m['status'])) }}</span></td>
                <td class="text-right" style="white-space:nowrap">
                  @if($m['late_fee'] > 0 && $m['status'] !== 'paid')
                    <form method="post" action="{{ admin_url('insurance-center/month/' . $m['id'] . '/remove-fee') }}" class="inline" data-confirm="Remove the late fee from {{ $m['label'] }}?">{{ csrf_field() }}<button class="btn btn-xs btn-default">Remove fee</button></form>
                  @endif
                  @if(in_array($m['status'], ['overdue', 'due', 'upcoming']) && $m['paid'] <= 0)
                    <form method="post" action="{{ admin_url('insurance-center/month/' . $m['id'] . '/waive') }}" class="inline" data-confirm="Waive {{ $m['label'] }}? The member will not be charged for it.">{{ csrf_field() }}<button class="btn btn-xs btn-link text-muted">Waive</button></form>
                  @endif
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@include('admin.insurance-center.pay-modal')
