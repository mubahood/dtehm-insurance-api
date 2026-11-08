<div class="ic wp">
  <div class="wr-pdf-bar">{!! \App\Admin\Helpers\WithdrawPdf::button('md') !!}<span class="text-muted">Pending withdraw requests, ready to print.</span></div>
  <div class="row">
    <div class="col-md-3 col-sm-6"><div class="ic-kpi"><span>Members with a PIN</span><b>{{ number_format($kpi['with_pin']) }}</b><small>of {{ number_format($kpi['members']) }} paid members</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="ic-kpi {{ $kpi['locked'] ? 'ic-kpi-danger' : 'ic-kpi-success' }}"><span>Locked right now</span><b>{{ $kpi['locked'] }}</b><small>after 5 wrong tries (30 min)</small></div></div>
    <div class="col-md-3 col-sm-6"><div class="ic-kpi"><span>Set by the office (30 days)</span><b>{{ $kpi['changed_30d'] }}</b></div></div>
    <div class="col-md-3 col-sm-6"><div class="ic-kpi"><span>Find a member</span>
      <form method="get" class="wp-search"><input type="text" name="q" value="{{ $q }}" class="form-control input-sm" placeholder="Name, phone, DTEHM or DIP ID" autofocus></form></div></div>
  </div>

  @if($q !== '')
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Results for "{{ $q }}"</h3></div>
      <div class="box-body no-padding"><table class="table table-hover ic-table">
        @forelse($results as $u)
          @php($p = $pins->get($u->id))
          <tr>
            <td><b>{{ $u->name }}</b><br><small class="text-muted">{{ $u->phone_number }} · {{ $u->dtehm_member_id ?: $u->business_name }}</small></td>
            <td>@include('admin.withdraw-pins.badge', ['pin' => $p])</td>
            <td class="text-right"><a class="btn btn-sm btn-primary" href="{{ admin_url('withdraw-pins/' . $u->id) }}"><i class="fa fa-lock"></i> Manage PIN</a></td>
          </tr>
        @empty
          <tr><td class="text-center text-muted ic-empty">No member matches.</td></tr>
        @endforelse
      </table></div></div>
  @endif

  <div class="row">
    <div class="col-md-6">
      <div class="box box-danger"><div class="box-header with-border"><h3 class="box-title">Locked out now</h3></div>
        <div class="box-body no-padding"><table class="table ic-table">
          @forelse($locked as $p)
            <tr>
              <td><b>{{ optional($p->user)->name }}</b><br><small class="text-muted">{{ optional($p->user)->phone_number }} · unlocks {{ $p->locked_until->diffForHumans() }}</small></td>
              <td class="text-right" style="white-space:nowrap">
                <form method="post" action="{{ admin_url('withdraw-pins/' . $p->user_id . '/unlock') }}" class="inline" data-confirm="Unlock {{ optional($p->user)->name }}'s PIN now?">{{ csrf_field() }}<button class="btn btn-xs btn-success"><i class="fa fa-unlock"></i> Unlock</button></form>
                <a class="btn btn-xs btn-default" href="{{ admin_url('withdraw-pins/' . $p->user_id) }}">Manage</a>
              </td>
            </tr>
          @empty
            <tr><td class="text-center text-muted ic-empty">Nobody is locked out.</td></tr>
          @endforelse
        </table></div></div>
      <div class="box box-default"><div class="box-header with-border"><h3 class="box-title">Recent wrong tries</h3></div>
        <div class="box-body no-padding"><table class="table table-condensed ic-table">
          @forelse($struggling as $p)
            <tr><td><a href="{{ admin_url('withdraw-pins/' . $p->user_id) }}">{{ optional($p->user)->name }}</a></td><td class="text-right"><span class="label label-warning">{{ $p->failed_attempts }} wrong {{ $p->failed_attempts == 1 ? 'try' : 'tries' }}</span></td></tr>
          @empty
            <tr><td class="text-center text-muted ic-empty">None.</td></tr>
          @endforelse
        </table></div></div>
    </div>
    <div class="col-md-6">
      <div class="box box-default"><div class="box-header with-border"><h3 class="box-title">Recent PIN activity</h3></div>
        <div class="box-body no-padding"><table class="table table-condensed ic-table">
          @forelse($activity as $l)
            <tr>
              <td><a href="{{ admin_url('withdraw-pins/' . $l->user_id) }}"><b>{{ optional($l->user)->name ?? '#' . $l->user_id }}</b></a><br><small class="text-muted">{{ $l->label }}@if($l->actor_type === 'admin') by {{ optional($l->actor)->name ?? 'admin' }}@endif</small></td>
              <td class="text-right"><small class="text-muted">{{ optional($l->created_at)->diffForHumans() }}</small></td>
            </tr>
          @empty
            <tr><td class="text-center text-muted ic-empty">No PIN activity recorded yet.</td></tr>
          @endforelse
        </table></div></div>
    </div>
  </div>
</div>
