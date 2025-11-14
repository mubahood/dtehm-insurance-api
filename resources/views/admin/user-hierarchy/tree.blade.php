<div class="row">
    <!-- User Info Card -->
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-user"></i> User Information
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        @if($user->avatar)
                            <img src="{{ asset($user->avatar) }}" alt="{{ $user->name }}" class="img-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        @else
                            <div class="img-circle" style="width: 120px; height: 120px; background: #3c8dbc; color: white; display: flex; align-items: center; justify-content: center; font-size: 48px; margin: 0 auto;">
                                {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="col-md-5">
                        <table class="table table-condensed">
                            <tr>
                                <th style="width: 150px;">Full Name:</th>
                                <td><strong>{{ $user->name }}</strong></td>
                            </tr>
                            <tr>
                                <th>DIP ID:</th>
                                <td><span class="label label-primary">{{ $user->business_name ?? 'Not Generated' }}</span></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td>{{ $user->phone_number }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $user->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($user->status === 'Active')
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-default">{{ $user->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-5">
                        <table class="table table-condensed">
                            <tr>
                                <th style="width: 180px;">DTEHM Member:</th>
                                <td>
                                    @if($user->is_dtehm_member === 'Yes')
                                        <span class="label label-success">Yes</span>
                                        <br><small>{{ $user->dtehm_member_id }}</small>
                                    @else
                                        <span class="label label-default">No</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>DIP Member:</th>
                                <td>
                                    @if($user->is_dip_member === 'Yes')
                                        <span class="label label-success">Yes</span>
                                    @else
                                        <span class="label label-default">No</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Direct Sponsor:</th>
                                <td>
                                    @if($user->sponsor_id)
                                        @php
                                            $sponsor = \App\Models\User::where('business_name', $user->sponsor_id)->first();
                                        @endphp
                                        @if($sponsor)
                                            <a href="/admin/user-hierarchy/{{ $sponsor->id }}">
                                                <span class="label label-info">{{ $sponsor->business_name }}</span>
                                                <br><small>{{ $sponsor->name }}</small>
                                            </a>
                                        @else
                                            <span class="text-danger">Not Found</span>
                                        @endif
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Registered:</th>
                                <td>{{ $user->created_at ? $user->created_at->format('d M Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Total Downline:</th>
                                <td><span class="badge bg-blue" style="font-size: 14px;">{{ $user->getTotalDownlineCount() }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upline (Parents) Section -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-level-up"></i> Upline Hierarchy (Parents)
                </h3>
            </div>
            <div class="box-body">
                @php
                    $parents = $user->getAllParents();
                @endphp
                
                @if(empty($parents))
                    <p class="text-muted text-center">
                        <i class="fa fa-info-circle"></i> This user has no upline (no sponsor)
                    </p>
                @else
                    <div class="row">
                        @foreach($parents as $level => $parent)
                            <div class="col-md-3 col-sm-6">
                                <div class="small-box" style="background: {{ ['#00a65a', '#00c0ef', '#f39c12', '#dd4b39', '#605ca8', '#d81b60', '#39cccc', '#3d9970', '#01ff70', '#ff851b'][intval(str_replace('parent_', '', $level)) - 1] ?? '#3c8dbc' }}; color: white;">
                                    <div class="inner" style="min-height: 100px;">
                                        <h4 style="margin-top: 5px;">{{ strtoupper(str_replace('_', ' ', $level)) }}</h4>
                                        <p style="font-size: 13px; margin-bottom: 5px;">
                                            <strong>{{ $parent->name }}</strong><br>
                                            <small>{{ $parent->business_name }}</small><br>
                                            <small>{{ $parent->phone_number }}</small>
                                        </p>
                                    </div>
                                    <a href="/admin/user-hierarchy/{{ $parent->id }}" class="small-box-footer" style="color: white;">
                                        View Details <i class="fa fa-arrow-circle-right"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Downline (Generations) Section -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-level-down"></i> Downline Network (10 Generations)
                </h3>
            </div>
            <div class="box-body">
                @php
                    $generations = $user->getAllGenerations();
                    $totalDownline = $user->getTotalDownlineCount();
                @endphp
                
                @if($totalDownline === 0)
                    <p class="text-muted text-center">
                        <i class="fa fa-info-circle"></i> This user has no downline yet
                    </p>
                @else
                    <!-- Generation Statistics -->
                    <div class="row" style="margin-bottom: 20px;">
                        @foreach($generations as $genKey => $genUsers)
                            @php
                                $genNumber = intval(str_replace('gen_', '', $genKey));
                                $count = $genUsers->count();
                                $colors = ['success', 'info', 'warning', 'danger', 'primary', 'purple', 'teal', 'olive', 'lime', 'orange'];
                                $color = $colors[$genNumber - 1] ?? 'default';
                            @endphp
                            <div class="col-md-12" style="margin-bottom: 15px;">
                                <h4 style="margin: 10px 0 5px 0;">
                                    <span class="label label-{{ $color }}">GENERATION {{ $genNumber }}</span>
                                    <span class="badge" style="font-size: 14px;">{{ $count }} {{ $count === 1 ? 'User' : 'Users' }}</span>
                                </h4>
                                
                                @if($count > 0)
                                    <div class="row">
                                        @foreach($genUsers as $genUser)
                                            <div class="col-md-3 col-sm-6" style="margin-bottom: 10px;">
                                                <div class="box box-solid" style="margin-bottom: 0;">
                                                    <div class="box-body box-profile" style="padding: 10px;">
                                                        <div class="text-center">
                                                            @if($genUser->avatar)
                                                                <img class="profile-user-img img-responsive img-circle" 
                                                                     src="{{ asset($genUser->avatar) }}" 
                                                                     alt="{{ $genUser->name }}"
                                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                                            @else
                                                                <div class="img-circle" style="width: 60px; height: 60px; background: #3c8dbc; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; margin: 0 auto 10px;">
                                                                    {{ strtoupper(substr($genUser->first_name, 0, 1)) }}{{ strtoupper(substr($genUser->last_name, 0, 1)) }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <h5 class="profile-username text-center" style="margin: 10px 0 5px 0; font-size: 14px;">
                                                            {{ $genUser->name }}
                                                        </h5>

                                                        <p class="text-muted text-center" style="margin: 0 0 10px 0; font-size: 11px;">
                                                            <span class="label label-primary">{{ $genUser->business_name }}</span><br>
                                                            {{ $genUser->phone_number }}<br>
                                                            <small>Downline: {{ $genUser->getTotalDownlineCount() }}</small>
                                                        </p>

                                                        <a href="/admin/user-hierarchy/{{ $genUser->id }}" class="btn btn-primary btn-block btn-xs">
                                                            <i class="fa fa-sitemap"></i> View Network
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted" style="margin-left: 20px; font-style: italic;">No users in this generation</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Generation Summary Stats -->
<div class="row">
    @php
        $genCounts = [];
        for($i = 1; $i <= 10; $i++) {
            $genCounts[$i] = $user->getGenerationCount($i);
        }
    @endphp
    
    @foreach($genCounts as $gen => $count)
        <div class="col-md-12-10 col-sm-6">
            <div class="info-box" style="min-height: 80px;">
                <span class="info-box-icon bg-{{ ['aqua', 'green', 'yellow', 'red', 'blue', 'purple', 'teal', 'olive', 'lime', 'orange'][$gen - 1] ?? 'gray' }}">
                    <i class="fa fa-users"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Generation {{ $gen }}</span>
                    <span class="info-box-number">{{ $count }} {{ $count === 1 ? 'User' : 'Users' }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<style>
    .col-md-12-10 {
        position: relative;
        min-height: 1px;
        padding-right: 15px;
        padding-left: 15px;
    }
    
    @media (min-width: 992px) {
        .col-md-12-10 {
            float: left;
            width: 20%;
        }
    }
    
    .bg-purple {
        background-color: #605ca8 !important;
    }
    
    .bg-teal {
        background-color: #39cccc !important;
    }
    
    .bg-olive {
        background-color: #3d9970 !important;
    }
    
    .bg-lime {
        background-color: #01ff70 !important;
    }
    
    .bg-orange {
        background-color: #ff851b !important;
    }
</style>
