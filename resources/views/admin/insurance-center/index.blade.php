@php($ugx = fn($v) => 'UGX ' . number_format((float) $v, 0))
@php($chip = ['overdue' => 'danger', 'due' => 'warning', 'up_to_date' => 'success', 'completed' => 'success', 'suspended' => 'default'])
<div class="ic">
  {{-- KPIs --}}
  <div class="row">
    <div class="col-md-3 col-sm-6"><div class="ic-kpi"><span>Active policies</span><b>{{ $kpi['active'] }}</b></div></div>
    <div class="col-md-3 col-sm-6"><div class="ic-kpi ic-kpi-danger"><span>Due now (all members)</span><b>{{ $ugx($kpi['due_now']) }}</b><small>{{ $kpi['overdue_members'] }} member(s) overdue</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="ic-kpi ic-kpi-success"><span>Collected this month</span><b>{{ $ugx($kpi['collected_month']) }}</b></div></div>
    <div class="col-md-3 col-sm-6"><div class="ic-kpi"><span>Still to collect (all months)</span><b>{{ $ugx($kpi['expected_year']) }}</b></div></div>
  </div>

  @if(session('tool_output'))
    <div class="box box-solid box-default"><div class="box-header"><h3 class="box-title">Result: {{ session('tool_output')['name'] }}</h3></div>
      <div class="box-body"><pre class="ic-pre">{{ session('tool_output')['output'] }}</pre></div></div>
  @endif

  <div class="row">
    {{-- Policies --}}
    <div class="col-md-8">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Policies</h3>
          <form method="get" class="ic-filter pull-right">
            <input type="text" name="q" value="{{ $q }}" class="form-control input-sm" placeholder="Name, phone, DTEHM ID, policy no." autofocus>
            <select name="state" class="form-control input-sm" onchange="this.form.submit()">
              @foreach(['attention' => 'Needs attention', 'overdue' => 'Overdue', 'due' => 'Due now', 'up_to_date' => 'Up to date', 'completed' => 'Fully paid', 'suspended' => 'Suspended', 'all' => 'All active'] as $k => $l)
                <option value="{{ $k }}" @if($state === $k) selected @endif>{{ $l }}</option>
              @endforeach
            </select>
            <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i></button>
          </form>
        </div>
        <div class="box-body table-responsive no-padding">
          <table class="table table-hover ic-table">
            <thead><tr><th>Member</th><th>Plan / months</th><th>Status</th><th class="text-right">Due now</th><th class="text-right">Paid</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
              @php($p = $r['policy']) @php($s = $r['s'])
              <tr>
                <td><a href="{{ admin_url('insurance-center/policy/' . $p->id) }}"><b>{{ optional($p->user)->name ?? 'User #' . $p->user_id }}</b></a><br><small class="text-muted">{{ optional($p->user)->phone_number }} · {{ $p->policy_number }}</small></td>
                <td>{{ $s['program_name'] }}<br><small class="text-muted">{{ $s['first_month'] }} – {{ $s['last_month'] }}</small></td>
                <td><span class="label label-{{ $chip[$s['state']] ?? 'default' }}">{{ $s['state_label'] }}</span></td>
                <td class="text-right"><b class="{{ $s['due_now_amount'] > 0 ? 'text-danger' : '' }}">{{ $ugx($s['due_now_amount']) }}</b>@if($s['late_fees'] > 0)<br><small class="text-muted">incl. {{ $ugx($s['late_fees']) }} fees</small>@endif</td>
                <td class="text-right">{{ $s['months_paid'] }}/{{ $s['months_total'] }}<br><small class="text-muted">{{ $ugx($s['paid_amount']) }}</small></td>
                <td class="text-right" style="white-space:nowrap">
                  @if($s['can_pay'])
                    <button type="button" class="btn btn-xs btn-success ic-pay-btn" data-url="{{ admin_url('insurance-center/policy/' . $p->id . '/pay') }}" data-name="{{ optional($p->user)->name }}" data-due="{{ (int) $s['due_now_amount'] }}" data-next="{{ (int) $s['next_due_amount'] }}" data-balance="{{ (int) $s['balance'] }}"><i class="fa fa-money"></i> Record payment</button>
                  @endif
                  <a class="btn btn-xs btn-default" href="{{ admin_url('insurance-center/policy/' . $p->id) }}">Open</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted ic-empty">Nothing here. @if($state === 'attention') Everyone is up to date. @endif</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Side: plans, tools, payments --}}
    <div class="col-md-4">
      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Plans</h3></div>
        <div class="box-body">
          @foreach($programs as $pr)
            <div class="ic-plan">
              <b>{{ $pr['p']->name }}</b> <small class="text-muted">{{ $ugx($pr['p']->premium_amount) }} / {{ strtolower($pr['p']->billing_frequency) }} · {{ $pr['p']->duration_months }} months</small><br>
              @if($pr['e']['open'])
                <span class="label label-success">Open for new members</span>
              @else
                <span class="label label-default">Closed</span> <small class="text-muted">{{ $pr['e']['reason'] }}</small>
              @endif
              <form method="post" action="{{ admin_url('insurance-center/program/' . $pr['p']->id . '/enrolment') }}" class="inline" data-confirm="{{ $pr['e']['open'] ? 'Close enrolment for this plan?' : 'Open enrolment for this plan?' }}">
                {{ csrf_field() }}<input type="hidden" name="open" value="{{ $pr['e']['open'] ? 0 : 1 }}">
                <button class="btn btn-xs btn-link">{{ $pr['e']['open'] ? 'Close enrolment' : 'Open enrolment' }}</button>
              </form>
            </div>
          @endforeach
        </div>
      </div>

      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Tools</h3></div>
        <div class="box-body ic-tools">
          @foreach([['maintain', 'Update overdue months & late fees', 'Runs every night at 01:00; run now after manual changes.', ''],
                    ['reconcile', 'Re-check Pesapal payments (30 days)', 'Applies any payment that was paid but not credited.', ''],
                    ['repair-preview', 'Check policies for problems', 'Read-only: shows money on wrong months or wrong dates.', ''],
                    ['repair-apply', 'Fix those problems', 'Moves recorded money to the oldest months. Run the check first.', 'Apply the fixes shown by the check?']] as [$k, $t, $h, $confirm])
            <form method="post" action="{{ admin_url('insurance-center/tool/' . $k) }}" @if($confirm) data-confirm="{{ $confirm }}" @endif>
              {{ csrf_field() }}
              <button class="btn btn-block btn-default text-left"><b>{{ $t }}</b><br><small class="text-muted">{{ $h }}</small></button>
            </form>
          @endforeach
        </div>
      </div>

      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Open online payments (30 days)</h3></div>
        <div class="box-body no-padding">
          <table class="table table-condensed">
            @forelse($openPayments as $op)
              <tr>
                <td><b>{{ optional($op->user)->name ?? '#' . $op->user_id }}</b><br><small class="text-muted">{{ $op->payment_type }} · {{ $op->created_at->diffForHumans() }}</small></td>
                <td class="text-right">{{ $ugx($op->amount) }}<br><span class="label label-{{ $op->status === 'COMPLETED' ? 'success' : 'warning' }}">{{ $op->status }}</span></td>
                <td class="text-right"><form method="post" action="{{ admin_url('insurance-center/payment/' . $op->id . '/recheck') }}">{{ csrf_field() }}<button class="btn btn-xs btn-default" title="Ask Pesapal for the real status"><i class="fa fa-refresh"></i></button></form></td>
              </tr>
            @empty
              <tr><td class="text-muted text-center ic-empty">No open payments.</td></tr>
            @endforelse
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@include('admin.insurance-center.pay-modal')
