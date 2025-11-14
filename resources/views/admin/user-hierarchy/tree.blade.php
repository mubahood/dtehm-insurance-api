<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-sitemap"></i> Network Hierarchy: <strong>{{ $user->name }}</strong>
        </h3>
        <div class="box-tools pull-right">
            <span class="label label-primary">{{ $user->business_name }}</span>
            @if($user->dtehm_member_id)
                <span class="label label-success">{{ $user->dtehm_member_id }}</span>
            @endif
            <span class="badge bg-blue">{{ $user->getTotalDownlineCount() }} Total Downline</span>
        </div>
    </div>
    <div class="box-body" style="padding: 15px 20px;">
        <div style="margin-bottom: 10px; padding: 8px; background: #f4f4f4; border-left: 3px solid #3c8dbc;">
            <strong>{{ $user->name }}</strong> 
            <small class="text-muted">| {{ $user->phone_number }}</small>
            @if($user->sponsor_id)
                @php
                    $sponsor = \App\Models\User::where('business_name', $user->sponsor_id)->orWhere('dtehm_member_id', $user->sponsor_id)->first();
                @endphp
                @if($sponsor)
                    | Sponsor: <a href="{{ admin_url('user-hierarchy/' . $sponsor->id) }}">{{ $sponsor->name }}</a>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Upline Section -->
@php
    $parents = $user->getAllParents();
@endphp

@if(!empty($parents))
<div class="box box-success collapsed-box">
    <div class="box-header with-border" style="cursor: pointer;" data-widget="collapse">
        <h3 class="box-title">
            <i class="fa fa-level-up"></i> Upline ({{ count($parents) }} Levels)
        </h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body" style="display: none; padding: 0;">
        <ul class="hierarchy-tree" style="list-style: none; padding-left: 0; margin: 0;">
            @foreach($parents as $level => $parent)
                <li style="border-bottom: 1px solid #f4f4f4;">
                    <div style="padding: 10px 15px;">
                        <i class="fa fa-user text-success"></i>
                        <strong>{{ str_replace('_', ' ', strtoupper($level)) }}:</strong>
                        <a href="{{ admin_url('user-hierarchy/' . $parent->id) }}">{{ $parent->name }}</a>
                        <small class="text-muted">({{ $parent->business_name }})</small>
                        <small class="text-muted">| {{ $parent->phone_number }}</small>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<!-- Downline Network Tree -->
<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-level-down"></i> Downline Network (10 Generations)
        </h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" id="expand-all">
                <i class="fa fa-plus-square-o"></i> Expand All
            </button>
            <button type="button" class="btn btn-box-tool" id="collapse-all">
                <i class="fa fa-minus-square-o"></i> Collapse All
            </button>
        </div>
    </div>
    <div class="box-body" style="padding: 0;">
        @php
            $totalDownline = $user->getTotalDownlineCount();
        @endphp
        
        @if($totalDownline === 0)
            <div style="padding: 20px; text-align: center;">
                <i class="fa fa-info-circle text-muted"></i>
                <span class="text-muted">This user has no downline yet</span>
            </div>
        @else
                    <ul class="hierarchy-tree" style="list-style: none; padding-left: 0; margin: 0;">
                        @for($gen = 1; $gen <= 10; $gen++)
                            @php
                                $genUsers = $user->getGenerationUsers($gen);
                                $count = $genUsers->count();
                            @endphp
                            
                            @if($count > 0)
                                <li class="generation-item" style="border-bottom: 1px solid #f4f4f4;">
                                    <div class="generation-header" style="padding: 12px 15px; background: #fafafa; cursor: pointer; display: flex; align-items: center; justify-content: space-between;" data-generation="{{ $gen }}">
                                        <div>
                                            <i class="fa fa-plus-square-o toggle-icon"></i>
                                            <strong style="margin-left: 8px;">Generation {{ $gen }}</strong>
                                            <span class="badge bg-{{ ['green', 'blue', 'yellow', 'red', 'purple', 'orange', 'teal', 'olive', 'aqua', 'navy'][$gen - 1] ?? 'gray' }}" style="margin-left: 10px;">{{ $count }}</span>
                                        </div>
                                    </div>
                                    <ul class="generation-children" style="display: none; list-style: none; padding-left: 0; margin: 0; background: #fff;">
                                        @foreach($genUsers as $genUser)
                                            <li style="border-bottom: 1px solid #f9f9f9;">
                                                <div style="padding: 8px 15px 8px 40px; display: flex; align-items: center; justify-content: space-between;">
                                                    <div style="flex: 1;">
                                                        <i class="fa fa-user text-muted" style="margin-right: 8px;"></i>
                                                        <a href="{{ admin_url('user-hierarchy/' . $genUser->id) }}" style="font-weight: 500;">
                                                            {{ $genUser->name }}
                                                        </a>
                                                        <small class="text-muted" style="margin-left: 8px;">
                                                            ({{ $genUser->business_name }})
                                                        </small>
                                                        <small class="text-muted">
                                                            | {{ $genUser->phone_number }}
                                                        </small>
                                                    </div>
                                                    <div>
                                                        @php
                                                            $userDownline = $genUser->getTotalDownlineCount();
                                                        @endphp
                                                        @if($userDownline > 0)
                                                            <span class="badge bg-blue" style="margin-right: 8px;" title="Total downline">
                                                                {{ $userDownline }}
                                                            </span>
                                                        @endif
                                                        <a href="{{ admin_url('user-hierarchy/' . $genUser->id) }}" class="btn btn-xs btn-primary" title="View network tree">
                                                            <i class="fa fa-sitemap"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        @endfor
                    </ul>
                @endif
            </div>
        </div>

<style>
    .generation-header:hover {
        background: #f0f0f0 !important;
    }
    
    .hierarchy-tree li:last-child {
        border-bottom: none !important;
    }
    
    .generation-children li:hover {
        background: #f9f9f9;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle individual generation
    document.querySelectorAll('.generation-header').forEach(function(header) {
        header.addEventListener('click', function() {
            const children = this.nextElementSibling;
            const icon = this.querySelector('.toggle-icon');
            
            if (children.style.display === 'none' || children.style.display === '') {
                children.style.display = 'block';
                icon.className = 'fa fa-minus-square-o toggle-icon';
            } else {
                children.style.display = 'none';
                icon.className = 'fa fa-plus-square-o toggle-icon';
            }
        });
    });
    
    // Expand all
    document.getElementById('expand-all').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.generation-children').forEach(function(children) {
            children.style.display = 'block';
        });
        document.querySelectorAll('.toggle-icon').forEach(function(icon) {
            icon.className = 'fa fa-minus-square-o toggle-icon';
        });
    });
    
    // Collapse all
    document.getElementById('collapse-all').addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.generation-children').forEach(function(children) {
            children.style.display = 'none';
        });
        document.querySelectorAll('.toggle-icon').forEach(function(icon) {
            icon.className = 'fa fa-plus-square-o toggle-icon';
        });
    });
});
</script>
