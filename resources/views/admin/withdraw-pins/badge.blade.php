@if(!$pin)
  <span class="label label-default">No PIN</span>
@elseif($pin->locked_until && $pin->locked_until->isFuture())
  <span class="label label-danger"><i class="fa fa-lock"></i> Locked</span>
@else
  <span class="label label-success">PIN set</span>@if($pin->failed_attempts > 0) <span class="label label-warning">{{ $pin->failed_attempts }} wrong</span>@endif
@endif
