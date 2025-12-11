<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\DtehmMembership;
use App\Models\MembershipPayment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;


class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Users';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->orderBy('id', 'desc');
        $grid->disableBatchActions();

        // ID Column
        $grid->column('id', __('ID'))
            ->sortable()
            ->width(60)
            ->style('font-weight: bold; color: #05179F;');

        $grid->column('avatar', __('Photo'))
            ->lightbox(['width' => 50, 'height' => 50])
            ->width(60);

        // Full Name Column
        $grid->column('name', __('Full Name'))
            ->display(function () {
                $children = User::where('parent_1', $this->id)->count();
                $url = admin_url('user-hierarchy/' . $this->id);
                $badge = $children > 0
                    ? '<span class="label label-success" style="font-size: 9px; margin-left: 4px;">' . $children . '</span>'
                    : '';
                return '<div style="line-height: 1.3;">
                <strong>' . trim($this->first_name . ' ' . $this->last_name) . '</strong>' . $badge . '
                <br>
                <a href="' . $url . '" target="_blank" style="font-size: 10px; color: #337ab7;" title="View Network Tree">
                    <i class="fa fa-sitemap"></i> View Network Tree
                </a>
            </div>';
            })
            ->sortable()
            ->width(180);

        // Gender Column
        $grid->column('sex', __('Gender'))
            ->label([
                'Male' => 'info',
                'Female' => 'danger',
            ])
            ->width(80);

        // Phone Number Column
        $grid->column('phone_number', __('Phone'))
            ->sortable()
            ->width(120);

        // Email Column
        $grid->column('email', __('Email'))
            ->sortable()
            ->hide()
            ->width(180);

        // User Type Column
        $grid->column('user_type', __('User Type'))
            ->label([
                'Admin' => 'danger',
                'Customer' => 'success',
                'Vendor' => 'warning',
            ])
            ->hide()
            ->filter([
                'Admin' => 'Admin',
                'Customer' => 'Customer',
                'Vendor' => 'Vendor',
            ])
            ->sortable()
            ->width(100);

        // Country Column
        $grid->column('country', __('Country'))
            ->hide()
            ->width(120);

        // Tribe Column
        $grid->column('tribe', __('Tribe'))
            ->width(120)
            ->hide();


        // Status Column
        $grid->column('status', __('Status'))
            ->label([
                'Active' => 'success',
                'Pending' => 'warning',
                'Banned' => 'danger',
                'Inactive' => 'default',
            ], 'Active')
            ->hide()
            ->filter([
                'Active' => 'Active',
                'Pending' => 'Pending',
                'Banned' => 'Banned',
                'Inactive' => 'Inactive',
            ])
            ->width(90);



        $grid->column('monthly_members', 'Monthly Members')->expand(function ($model) {
            $comments = [];
            $startDate = now()->subDays(30);
            $members = User::where('parent_1', $model->id)
                ->where('created_at', '>=', $startDate)
                ->get();
            foreach ($members as $member) {
                $comments[] = [
                    'DTEHM ID' => $member->id,
                    'Name' =>  $member->first_name . ' ' . $member->last_name,
                    'Contact' => $member->phone_number,
                ];
            }
            return new Table(['DTEHM ID', 'Name', 'Contact'], $comments);
        });

        //weekly_members
        $grid->column('weekly_members', 'Weekly Members')->expand(function ($model) {

            $startDate = now()->subDays(7);
            $comments = [];
            $members = User::where('parent_1', $model->id)
                ->where('created_at', '>=', $startDate)
                ->get();
            foreach ($members as $member) {
                $comments[] = [
                    'DTEHM ID' => $member->id,
                    'Name' =>  $member->first_name . ' ' . $member->last_name,
                    'Contact' => $member->phone_number,
                ];
            }

            return new Table(['DTEHM ID', 'Name', 'Contact'], $comments);
        });

        //all_time_members
        $grid->column('all_time_members', 'All Time Members')->expand(function ($model) {
            $comments = [];
            $members = User::where('parent_1', $model->id)
                ->get();
            foreach ($members as $member) {
                $comments[] = [
                    'DTEHM ID' => $member->id,
                    'Name' =>  $member->first_name . ' ' . $member->last_name,
                    'Contact' => $member->phone_number,
                ];
            }

            return new Table(['DTEHM ID', 'Name', 'Contact'], $comments);
        });

        // Products sold as sponsor
        $grid->column('products_as_sponsor', 'Sales as Sponsor')->expand(function ($model) {
            $comments = [];
            $products = \App\Models\OrderedItem::where('sponsor_user_id', $model->id)
                ->with('pro')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($products as $product) {
                $productName = $product->pro ? $product->pro->name : 'Product #' . $product->product;
                $comments[] = [
                    'Order ID' => $product->id,
                    'Product' => $productName,
                    'Qty' => $product->qty,
                    'Amount' => 'UGX ' . number_format($product->subtotal, 0),
                    'Date' => date('d M Y', strtotime($product->created_at)),
                ];
            }

            return new Table(['Order ID', 'Product', 'Qty', 'Amount', 'Date'], $comments);
        });

        // Products sold as stockist
        $grid->column('products_as_stockist', 'Sales as Stockist')->expand(function ($model) {
            $comments = [];
            $products = \App\Models\OrderedItem::where('stockist_user_id', $model->id)
                ->with('pro')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($products as $product) {
                $productName = $product->pro ? $product->pro->name : 'Product #' . $product->product;
                $comments[] = [
                    'Order ID' => $product->order,
                    'Product' => $productName,
                    'Qty' => $product->qty,
                    'Amount' => 'UGX ' . number_format($product->subtotal, 0),
                    'Commission' => 'UGX ' . number_format($product->commission_stockist ?? 0, 0),
                    'Date' => date('d M Y', strtotime($product->created_at)),
                ];
            }

            return new Table(['Order ID', 'Product', 'Qty', 'Amount', 'Commission', 'Date'], $comments);
        });


        // Date of Birth Column
        $grid->column('dob', __('DOB'))
            ->display(function ($dob) {
                if (empty($dob) || $dob == '0000-00-00') {
                    return '-';
                }
                return date('d M Y', strtotime($dob));
            })
            ->hide()
            ->width(100);

        // Created At Column
        $grid->column('created_at', __('Registered'))
            ->display(function ($created_at) {
                return date('d M Y', strtotime($created_at));
            })
            ->sortable()
            ->hide()
            ->width(100);

        // Quick Search
        $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number', 'business_name', 'sponsor_id')->placeholder('Search by name, email, phone, or DIP ID');

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('email', 'Email');
            $filter->like('business_name', 'DIP ID');
            $filter->like('dtehm_member_id', 'DTEHM ID');
            $filter->like('sponsor_id', 'Sponsor ID');

            $filter->equal('sex', 'Gender')->radio([
                '' => 'All',
                'Male' => 'Male',
                'Female' => 'Female',
            ]);

            $filter->equal('is_dtehm_member', 'DTEHM Member')->radio([
                '' => 'All',
                'Yes' => 'Yes',
                'No' => 'No',
            ]);

            $filter->equal('is_dip_member', 'DIP Member')->radio([
                '' => 'All',
                'Yes' => 'Yes',
                'No' => 'No',
            ]);

            $filter->equal('dtehm_membership_is_paid', 'DTEHM Paid')->radio([
                '' => 'All',
                'Yes' => 'Paid',
                'No' => 'Unpaid',
            ]);

            $filter->like('country', 'Country');
            $filter->like('tribe', 'Tribe');
            $filter->between('created_at', 'Registered Date')->date();
        });


        // DIP ID Column
        $grid->column('dtehm_member_id', __('DTEHM ID'))
            ->label('primary')
            ->sortable();



        // Sponsor Column
        $grid->column('sponsor_id', __('Sponsor'))
            ->display(function () {
                if (empty($this->sponsor_id)) {
                    return '<span class="text-muted">-</span>';
                }
                $sponsor = \App\Models\User::where('id', $this->parent_1)->first();
                if ($sponsor) {
                    return '<span class="label label-success" style="font-size: 10px;">' . $this->sponsor_id . '</span><br>' .
                        '<small class="text-muted">' . $sponsor->name . '</small>';
                }
                return '<span class="label label-warning" style="font-size: 10px;">' . $this->sponsor_id . '</span><br>' .
                    '<small class="text-danger">Not Found</small>';
            })
            ->sortable();

        //balance
        $grid->column('account_balance', __('Balance (UGX) '))
            ->display(function ($balance) {
                return   number_format($this->account_balance, 0);
            })
            ->width(120);

        //total_points
        $grid->column('total_points', __('Points '))
            ->display(function ($points) {
                return   number_format($this->total_points, 0);
            })
            ->sortable();


        // Add SMS action column with buttons
        $grid->column('sms_actions', 'SMS')->display(function () {
            $userId = $this->id;
            return '
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <a href="' . url('/admin/users/' . $userId . '/send-credentials') . '" 
                       target="_blank" 
                       class="btn btn-xs btn-success" 
                       style="padding: 2px 6px; font-size: 11px; white-space: nowrap;"
                       title="Send login credentials via SMS">
                        <i class="fa fa-key"></i> Credentials
                    </a>
                    <a href="' . url('/admin/users/' . $userId . '/send-welcome') . '" 
                       target="_blank" 
                       class="btn btn-xs btn-info" 
                       style="padding: 2px 6px; font-size: 11px; white-space: nowrap;"
                       title="Send welcome message via SMS">
                        <i class="fa fa-envelope"></i> Welcome
                    </a>
                </div>
            ';
        })->width(100);

        // Disable view action since we don't use it
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        // Prevent detail view from being accessed during creation
        if ($id === 'create' || !is_numeric($id)) {
            abort(404);
        }

        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('username', __('Username'));
        $show->field('password', __('Password'));
        $show->field('first_name', __('First name'));
        $show->field('last_name', __('Last name'));
        $show->field('reg_date', __('Reg date'));
        $show->field('last_seen', __('Last seen'));
        $show->field('email', __('Email'));
        $show->field('approved', __('Approved'));
        $show->field('profile_photo', __('Profile photo'));
        $show->field('user_type', __('User type'));
        $show->field('sex', __('Sex'));
        $show->field('reg_number', __('Reg number'));
        $show->field('country', __('Country'));
        $show->field('occupation', __('Occupation'));
        $show->field('profile_photo_large', __('Profile photo large'));
        $show->field('phone_number', __('Phone number'));
        $show->field('location_lat', __('Location lat'));
        $show->field('location_long', __('Location long'));
        $show->field('facebook', __('Facebook'));
        $show->field('twitter', __('Twitter'));
        $show->field('whatsapp', __('Whatsapp'));
        $show->field('linkedin', __('Linkedin'));
        $show->field('website', __('Website'));
        $show->field('other_link', __('Other link'));
        $show->field('cv', __('Cv'));
        $show->field('language', __('Language'));
        $show->field('about', __('About'));
        $show->field('address', __('Address'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('remember_token', __('Remember token'));
        $show->field('avatar', __('Avatar'));
        $show->field('name', __('Name'));
        $show->field('campus_id', __('Campus id'));
        $show->field('complete_profile', __('Complete profile'));
        $show->field('title', __('Title'));
        $show->field('dob', __('Dob'));
        $show->field('intro', __('Intro'));
        $show->field('business_name', __('Business name'));
        $show->field('business_license_number', __('Business license number'));
        $show->field('business_license_issue_authority', __('Business license issue authority'));
        $show->field('business_license_issue_date', __('Business license issue date'));
        $show->field('business_license_validity', __('Business license validity'));
        $show->field('business_address', __('Business address'));
        $show->field('business_phone_number', __('Business phone number'));
        $show->field('business_whatsapp', __('Business whatsapp'));
        $show->field('business_email', __('Business email'));
        $show->field('business_logo', __('Business logo'));
        $show->field('business_cover_photo', __('Business cover photo'));
        $show->field('business_cover_details', __('Business cover details'));

        return $show;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        // First, let parent handle the update
        $response = parent::update($id);

        // Then trigger membership creation
        $this->handleMembershipCreation($id);

        return $response;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        // First, let parent handle the creation
        $response = parent::store();

        // Get the newly created user ID from the response
        // Laravel-Admin returns redirect, so we need to get the last inserted user
        $user = User::latest('id')->first();
        if ($user) {
            $this->handleMembershipCreation($user->id);
        }

        return $response;
    }

    /**
     * Handle membership creation for a user
     *
     * @param int $userId
     * @return void
     */
    protected function handleMembershipCreation($userId)
    {
        $admin = Admin::user();
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        // If no admin user (e.g., CLI context), use a default admin or system user
        if (!$admin) {
            // Try to get first admin user or use null
            $admin = User::where('user_type', 'Admin')->first();
        }

        // Handle membership creation silently

        try {
            // Check if user is marked as DTEHM member
            if ($user->is_dtehm_member == 'Yes') {
                // Check if DTEHM membership already exists
                $existingDtehm = \App\Models\DtehmMembership::where('user_id', $user->id)
                    ->where('status', 'CONFIRMED')
                    ->first();

                if (!$existingDtehm) {
                    \Log::info('Creating DTEHM membership for user', ['user_id' => $user->id]);

                    $adminId = $admin ? $admin->id : null;
                    $adminUsername = $admin ? $admin->username : 'System';

                    // Create DTEHM Membership (76,000 UGX)
                    $dtehm = \App\Models\DtehmMembership::create([
                        'user_id' => $user->id,
                        'amount' => 76000,
                        'status' => 'CONFIRMED',
                        'payment_method' => 'CASH',
                        'registered_by_id' => $adminId,
                        'created_by' => $adminId,
                        'confirmed_by' => $adminId,
                        'confirmed_at' => now(),
                        'payment_date' => now(),
                        'description' => 'Auto-created by admin ' . $adminUsername . ' via web portal during user registration',
                    ]);

                    // Update user model with DTEHM membership info
                    $user->dtehm_membership_paid_at = now();
                    $user->dtehm_membership_amount = 76000;
                    $user->dtehm_membership_payment_id = $dtehm->id;
                    $user->dtehm_membership_is_paid = 'Yes';
                    $user->dtehm_membership_paid_date = now();
                    $user->dtehm_member_membership_date = now();
                    $user->saveQuietly(); // Use saveQuietly to avoid triggering observer again

                    \Log::info('DTEHM membership created successfully', ['dtehm_id' => $dtehm->id]);

                    // CREATE SPONSOR COMMISSION FOR DTEHM MEMBERSHIP (10,000 UGX)
                    $this->createSponsorCommission($user, $dtehm->id, 'dtehm_membership');
                }
                // If membership already exists, do nothing (no need to log)
            }

            // Check if user is marked as DIP member
            if ($user->is_dip_member == 'Yes') {
                // Check if DIP membership already exists
                $existingDip = \App\Models\MembershipPayment::where('user_id', $user->id)
                    ->where('status', 'CONFIRMED')
                    ->first();

                if (!$existingDip) {
                    \Log::info('Creating DIP membership for user', ['user_id' => $user->id]);

                    $adminId = $admin ? $admin->id : null;
                    $adminUsername = $admin ? $admin->username : 'System';

                    // Create Regular DIP Membership (20,000 UGX)
                    $membership = \App\Models\MembershipPayment::create([
                        'user_id' => $user->id,
                        'amount' => 20000,
                        'membership_type' => 'LIFE',
                        'status' => 'CONFIRMED',
                        'payment_method' => 'CASH',
                        'created_by' => $adminId,
                        'updated_by' => $adminId,
                        'registered_by_id' => $adminId,
                        'description' => 'Auto-created by admin ' . $adminUsername . ' via web portal during user registration',
                    ]);


                    \Log::info('DIP membership created successfully', ['membership_id' => $membership->id]);
                }
                // If membership already exists, do nothing (no need to log)
            }
        } catch (\Exception $e) {
            admin_toastr('Membership creation failed: ' . $e->getMessage(), 'error');
            \Log::error('Auto-membership creation failed', [
                'user_id' => $user->id,
                'admin_id' => $admin ? $admin->id : null,
                'is_dtehm_member' => $user->is_dtehm_member,
                'is_dip_member' => $user->is_dip_member,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());
        $form->disableCreatingCheck();
        $form->disableViewCheck();
        $form->disableReset();

        if ($form->isCreating()) {
            // SIMPLIFIED FORM FOR USER CREATION - Only Essential Info
            $form->hidden('user_type')->value('Customer');

            $form->divider('Basic Information');

            $form->row(function ($row) {
                $row->width(3)->text('first_name', __('First Name'))
                    ->rules('required')
                    ->required()
                    ->help('Required field');
                $row->width(3)->text('last_name', __('Last Name'))
                    ->rules('required')
                    ->required()
                    ->help('Required field');
                $row->width(3)->radio('sex', __('Gender'))
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ])
                    ->rules('required')
                    ->default('Male');
                $row->width(3)->text('phone_number', __('Phone Number'))
                    ->rules('required')
                    ->required()
                    ->help('Required field');
            });

            $form->row(function ($row) {

                $sponsors = [];
                foreach (User::where('is_dtehm_member', 'Yes')->orderBy('dtehm_member_id', 'asc')->get() as $sponsor) {
                    $sponsors[$sponsor->dtehm_member_id] = $sponsor->dtehm_member_id . ' - ' . $sponsor->first_name . ' ' . $sponsor->last_name;
                }

                $row->width(3)->select('is_dtehm_member', __('DTEHM Member?'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])
                    ->required()
                    ->rules('required');
                $row->width(3)->select('is_dip_member', __('DIP Member?'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])
                    ->rules('required')
                    ->required();

                $row->width(3)->select('sponsor_id', __('Sponsor ID'))
                    ->options($sponsors)
                    ->rules('required')
                    ->required();
                /*                 $row->width(2)->select('is_stockist', __('Is Stockist?'))
                    ->options([
                        'Yes' => 'Yes',
                        'No' => 'No',
                    ])
                    ->rules('required')
                    ->default('No');
                $row->width(2)->text('stockist_area', __('Area of operation'));
 */

                $row->width(3)->select('stockist_area', __('Center'))
                    ->options(function () {
                        return \App\Models\User::where(
                            'is_stockist',
                            'Yes'
                        )
                            ->orderBy('name', 'asc')
                            ->get()
                            ->mapWithKeys(function ($user) {
                                $label = $user->name;
                                if ($user->dtehm_member_id) {
                                    $label .= " ({$user->dtehm_member_id})";
                                } elseif ($user->business_name) {
                                    $label .= " ({$user->business_name})";
                                }
                                return [$user->name => $label];
                            });
                    })
                    ->rules('required')
                    ->required();


                /* 
                    $row->width(6)->password('password', __('Password'))
                    ->rules('nullable|confirmed|min:6')
                    ->help('Leave blank to keep current password (when editing). Minimum 6 characters.')
                    ->creationRules('required|min:6');

                $row->width(6)->password('password_confirmation', __('Confirm Password'))
                    ->rules('nullable|min:6')
                    ->help('Re-enter password for confirmation'); */
            });



            return $form;
        }

        // ==================== FULL FORM FOR EDITING EXISTING USERS ====================


        $form->row(function ($row) {
            $row->width(3)->text('first_name', __('First Name'))
                ->rules('required')
                ->help('Required');
            $row->width(3)->text('last_name', __('Last Name'))
                ->rules('required')
                ->help('Required');
            $row->width(3)->text('phone_number', __('Phone Number'))
                ->rules('required')
                ->help('Required');
            $row->width(3)->radio('sex', __('Gender'))
                ->options(['Male' => 'Male', 'Female' => 'Female'])
                ->rules('required')
                ->default('Male');
        });

        // SECTION 2: MEMBERSHIP INFORMATION
        $form->divider('Membership Information');

        $form->row(function ($row) {
            $sponsors = [];
            foreach (
                User::where('is_dtehm_member', 'Yes')
                    ->orderBy('dtehm_member_id', 'asc')
                    ->get() as $sponsor
            ) {
                $sponsors[$sponsor->dtehm_member_id] = $sponsor->dtehm_member_id . ' - ' . $sponsor->first_name . ' ' . $sponsor->last_name;
            }

            $row->width(3)->select('sponsor_id', __('Sponsor ID'))
                ->options($sponsors)
                ->required();

            $row->width(2)->select('is_dip_member', __('DIP Member?'))
                ->options(['Yes' => 'Yes', 'No' => 'No']);
            $row->width(2)->select('is_dtehm_member', __('DTEHM Member?'))
                ->options(['Yes' => 'Yes', 'No' => 'No']);


            $row->width(2)->select('is_stockist', __('Is Stockist?'))
                ->options([
                    'Yes' => 'Yes',
                    'No' => 'No',
                ])
                ->rules('required')
                ->default('No');

            $row->width(3)->text('stockist_area', __('Area of operation'));
            /*    $row->width(3)->image('avatar', __('Profile Photo'))
                ->uniqueName(); */
            $row->width()->hidden('dtehm_membership_is_paid', __('DTEHM Member ID'))
                ->default('Yes');
        });
        /*
        
        $row->width(6)->text('business_license_number', __('Group/License'))
                ->help('Business license or group number');
            
        
        */

        $countries = [
            'Uganda' => 'Uganda',
            'Kenya' => 'Kenya',
            'Tanzania' => 'Tanzania',
            'Rwanda' => 'Rwanda',
            'Burundi' => 'Burundi',
            'South Sudan' => 'South Sudan',
            'DRC' => 'DRC',
        ];

        $tribes = [
            'Acholi' => 'Acholi',
            'Alur' => 'Alur',
            'Baganda' => 'Baganda',
            'Bagisu' => 'Bagisu',
            'Bagwere' => 'Bagwere',
            'Banyankole' => 'Banyankole',
            'Banyoro' => 'Banyoro',
            'Bakonzo' => 'Bakonzo',
            'Basoga' => 'Basoga',
            'Batoro' => 'Batoro',
            'Iteso' => 'Iteso',
            'Japadhola' => 'Japadhola',
            'Kakwa' => 'Kakwa',
            'Karamojong' => 'Karamojong',
            'Langi' => 'Langi',
            'Lugbara' => 'Lugbara',
            'Madi' => 'Madi',
            'Other' => 'Other',
        ];

        /*     $form->row(function ($row) {
            $row->width(12)->text('address', __('Home Address'))
                ->rules('required')
                ->help('Required - permanent home address');
        }); */

        /*  $form->row(function ($row) use ($countries, $tribes) {
            $row->width(6)->select('country', __('Country'))
                ->options($countries)
                ->default('Uganda')
                ->help('Country of residence');

            $row->width(6)->select('tribe', __('Tribe'))
                ->options($tribes)
                ->help('Select tribe');
        }); */

        // SECTION 5: ACCOUNT & SYSTEM SETTINGS
        $form->divider('Account & System Settings');

        $form->row(function ($row) {
            $row->width(4)->radio('user_type', __('User Type'))
                ->options(['Customer' => 'Customer', 'Admin' => 'Admin'])
                ->default('Customer')
                ->help('Customer or Admin');

            $row->width(4)->radio('status', __('Account Status'))
                ->options([
                    'Active' => 'Active',
                    'Pending' => 'Pending',
                    'Inactive' => 'Inactive',
                    'Banned' => 'Banned',
                ])
                ->default('Active')
                ->help('Current status');

            $roleModel = config('admin.database.roles_model');
            $row->width(4)->multipleSelect('roles', __('Admin Roles'))
                ->options($roleModel::all()->pluck('name', 'id'))
                ->help('For admin users only');
        });



        // Password Management - Conditional Display
        if ($form->isCreating()) {
            // For NEW users: Password fields are always shown and REQUIRED
            $form->row(function ($row) {
                $row->width(6)->password('password', __('Password'))
                    ->rules('required|confirmed|min:6')
                    ->help('Required field. Minimum 6 characters.')
                    ->required();

                $row->width(6)->password('password_confirmation', __('Confirm Password'))
                    ->rules('required|min:6')
                    ->help('Re-enter password for confirmation')
                    ->required();
            });
        } else {
            // For EDITING users: Show toggle option to change password
            $form->divider('Password Management');

            $form->row(function ($row) {
                $row->width(12)->radio('change_password_toggle', __('Change Password?'))
                    ->options([
                        'No' => 'No - Keep current password',
                        'Yes' => 'Yes - Set new password',
                    ])
                    ->default('No')
                    ->help('Select "Yes" only if you want to change the user\'s password')
                    ->when('Yes', function (Form $form) {
                        $form->row(function ($row) {
                            $row->width(6)->password('password', __('New Password'));

                            $row->width(6)->password('password_confirmation', __('Confirm New Password'));
                        });
                    });
            });
        }

        // Auto-generate name field from first_name and last_name
        $form->saving(function (Form $form) {
            try {

                // VALIDATE SPONSOR ID - MUST EXIST IN SYSTEM
                if (!empty($form->sponsor_id)) {
                    // Try to find sponsor by DIP ID first
                    $sponsor = User::where('business_name', $form->sponsor_id)->first();

                    // If not found, try by DTEHM Member ID
                    if (!$sponsor) {
                        $sponsor = User::where('dtehm_member_id', $form->sponsor_id)->first();
                    }

                    // If still not found, show error
                    if (!$sponsor) {
                        $errorMsg = "Invalid Sponsor ID: {$form->sponsor_id}. Sponsor must be an existing user in the system.";
                        \Log::error($errorMsg);
                        admin_error('Validation Error', $errorMsg);
                        return false;
                    }

                    \Log::info('Sponsor validated successfully', [
                        'sponsor_id' => $form->sponsor_id,
                        'sponsor_user_id' => $sponsor->id,
                        'sponsor_name' => $sponsor->name,
                    ]);
                } else if ($form->isCreating()) {
                    // Sponsor ID is required for new users
                    $errorMsg = "Sponsor ID is required. No user can be registered without a sponsor.";
                    \Log::error($errorMsg);
                    admin_error('Validation Error', $errorMsg);
                    return false;
                }

                // Auto-generate full name from first_name and last_name
                if ($form->first_name && $form->last_name) {
                    $form->name = trim($form->first_name . ' ' . $form->last_name);
                }

                // FOR NEW USERS: Auto-fill required fields
                if ($form->isCreating()) {
                    // 1. Set username to phone_number
                    if ($form->phone_number) {
                        $form->username = $form->phone_number;
                    }

                    // 2. Password is handled by form validation (required field)
                    // Hash the password if provided
                    if (!empty($form->password)) {
                        $form->password = bcrypt($form->password);
                    }

                    // 3. Set registered_by_id to current admin
                    $form->registered_by_id = \Admin::user()->id;

                    // 4. Set default values for required fields
                    if (!$form->user_type) {
                        $form->user_type = 'Customer';
                    }

                    if (!$form->status) {
                        $form->status = 'Active';
                    }

                    // 5. Set default country
                    if (!$form->country) {
                        $form->country = 'Uganda';
                    }

                    // 6. Auto-mark membership paid fields if member type is selected
                    if ($form->is_dtehm_member == 'Yes') {
                        $form->dtehm_membership_is_paid = 'Yes';
                        $form->dtehm_membership_paid_date = now();
                        $form->dtehm_membership_paid_amount = 76000;
                        $form->dtehm_member_membership_date = now();
                    }
                } else {
                    // FOR UPDATES: Handle password change based on toggle
                    if ($form->change_password_toggle == 'Yes' && !empty($form->password)) {
                        // Admin wants to change password AND provided a new password
                        $form->password = bcrypt($form->password);
                        \Log::info('Password changed by admin', [
                            'user_id' => $form->model()->id,
                            'admin_id' => \Admin::user()->id,
                            'admin_name' => \Admin::user()->name,
                        ]);
                    } else {
                        // Admin doesn't want to change password OR didn't provide one - remove password from input
                        $form->ignore(['password', 'password_confirmation', 'change_password_toggle']);
                        \Log::info('Password change skipped', [
                            'user_id' => $form->model()->id,
                            'toggle' => $form->change_password_toggle ?? 'Not set',
                            'password_provided' => !empty($form->password) ? 'Yes' : 'No',
                        ]);
                    }

                    // Auto-mark membership paid fields if changed to Yes
                    if ($form->is_dtehm_member == 'Yes' && $form->model()->is_dtehm_member != 'Yes') {
                        $form->dtehm_membership_is_paid = 'Yes';
                        $form->dtehm_membership_paid_date = now();
                        $form->dtehm_membership_paid_amount = 76000;
                        $form->dtehm_member_membership_date = now();
                    }
                }

                \Log::info('============ SAVING HOOK END (SUCCESS) ============');
            } catch (\Exception $e) {
                \Log::error('============ SAVING HOOK FAILED ============', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                admin_error('Error', 'Failed to save user: ' . $e->getMessage());
                return false;
            }
        });

        // Success message with validation handling
        $form->saved(function (Form $form) {
            \Log::info('============ SAVED HOOK TRIGGERED ============', [
                'user_id' => $form->model()->id,
            ]);

            $admin = \Admin::user();
            $user = $form->model();

            // Reload user to get latest data
            $user = \App\Models\User::find($user->id);

            try {
                $membershipCreated = false;
                $messages = [];

                \Log::info('Checking membership creation', [
                    'user_id' => $user->id,
                    'is_dtehm_member' => $user->is_dtehm_member,
                    'is_dip_member' => $user->is_dip_member,
                ]);

                // Check if user is marked as DTEHM member
                if ($user->is_dtehm_member == 'Yes') {
                    // Check if DTEHM membership already exists
                    $existingDtehm = \App\Models\DtehmMembership::where('user_id', $user->id)
                        ->where('status', 'CONFIRMED')
                        ->first();

                    if (!$existingDtehm) {
                        \Log::info('Creating DTEHM membership for user', ['user_id' => $user->id]);

                        // Create DTEHM Membership (76,000 UGX)
                        $dtehm = \App\Models\DtehmMembership::create([
                            'user_id' => $user->id,
                            'amount' => 76000,
                            'status' => 'CONFIRMED',
                            'payment_method' => 'CASH',
                            'registered_by_id' => $admin->id,
                            'created_by' => $admin->id,
                            'confirmed_by' => $admin->id,
                            'confirmed_at' => now(),
                            'payment_date' => now(),
                            'description' => 'Auto-created by admin ' . $admin->username . ' via web portal during user registration',
                        ]);

                        // Update user model with DTEHM membership info
                        $user->dtehm_membership_paid_at = now();
                        $user->dtehm_membership_amount = 76000;
                        $user->dtehm_membership_payment_id = $dtehm->id;
                        $user->dtehm_membership_is_paid = 'Yes';
                        $user->dtehm_membership_paid_date = now();
                        $user->dtehm_member_membership_date = now();
                        $user->save();

                        $membershipCreated = true;
                        $messages[] = 'DTEHM membership (UGX 76,000) created and marked as PAID';

                        \Log::info('DTEHM membership created successfully', ['dtehm_id' => $dtehm->id]);
                    } else {
                        \Log::info('DTEHM membership already exists', ['user_id' => $user->id]);
                    }
                }

                // Check if user is marked as DIP member
                if ($user->is_dip_member == 'Yes') {
                    // Check if DIP membership already exists
                    $existingDip = \App\Models\MembershipPayment::where('user_id', $user->id)
                        ->where('status', 'CONFIRMED')
                        ->first();

                    if (!$existingDip) {
                        \Log::info('Creating DIP membership for user', ['user_id' => $user->id]);

                        // Create Regular DIP Membership (20,000 UGX)
                        $membership = \App\Models\MembershipPayment::create([
                            'user_id' => $user->id,
                            'amount' => 20000,
                            'membership_type' => 'LIFE',
                            'status' => 'CONFIRMED',
                            'payment_method' => 'CASH',
                            'created_by_id' => $admin->id,
                            'updated_by_id' => $admin->id,
                            'description' => 'Auto-created by admin ' . $admin->username . ' via web portal during user registration',
                        ]);

                        $membershipCreated = true;
                        $messages[] = 'DIP membership (UGX 20,000) created and marked as PAID';

                        \Log::info('DIP membership created successfully', ['membership_id' => $membership->id]);
                    } else {
                        \Log::info('DIP membership already exists', ['user_id' => $user->id]);
                    }
                }

                // Display success message
                if ($membershipCreated) {
                    $action = $form->isCreating() ? 'created' : 'updated';
                    $message = 'User ' . $action . ' successfully';
                    if (count($messages) > 0) {
                        $message .= ' with ' . implode(' and ', $messages);
                    }
                    admin_toastr($message, 'success');
                } else {
                    $action = $form->isCreating() ? 'created' : 'updated';
                    admin_toastr('User ' . $action . ' successfully', 'success');
                }
            } catch (\Exception $e) {
                $action = $form->isCreating() ? 'created' : 'updated';
                admin_toastr('User ' . $action . ' but membership creation failed: ' . $e->getMessage(), 'error');
                \Log::error('Auto-membership creation failed', [
                    'user_id' => $user->id,
                    'admin_id' => $admin->id,
                    'is_dtehm_member' => $user->is_dtehm_member,
                    'is_dip_member' => $user->is_dip_member,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });

        // Hide password confirmation and change_password_toggle from database
        $form->ignore(['password_confirmation', 'change_password_toggle']);

        // Form configuration
        // $form->disableCreatingCheck();
        // $form->disableEditingCheck();
        $form->disableViewCheck();

        // Tools configuration
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }

    /**
     * Create sponsor commission for DTEHM membership payment
     * 
     * @param User $user The user who paid the membership
     * @param int $membershipId The DTEHM membership ID
     * @param string $source The source of commission (dtehm_membership)
     * @return void
     */
    protected function createSponsorCommission($user, $membershipId, $source = 'dtehm_membership')
    {
        try {
            \Log::info('============ CREATING SPONSOR COMMISSION ============', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'sponsor_id' => $user->sponsor_id,
                'membership_id' => $membershipId,
                'source' => $source,
            ]);

            // Check if user has a sponsor
            if (empty($user->sponsor_id)) {
                \Log::warning('User has no sponsor ID - skipping commission', ['user_id' => $user->id]);
                return;
            }

            // Find the sponsor user
            $sponsor = User::where('business_name', $user->sponsor_id)->first();
            if (!$sponsor) {
                $sponsor = User::where('dtehm_member_id', $user->sponsor_id)->first();
            }

            if (!$sponsor) {
                \Log::error('Sponsor not found in system', [
                    'sponsor_id' => $user->sponsor_id,
                    'user_id' => $user->id,
                ]);
                return;
            }

            \Log::info('Sponsor found', [
                'sponsor_user_id' => $sponsor->id,
                'sponsor_name' => $sponsor->name,
                'sponsor_dip_id' => $sponsor->business_name,
                'sponsor_dtehm_id' => $sponsor->dtehm_member_id,
            ]);

            // Check if commission already exists for this membership
            $existingCommission = \App\Models\AccountTransaction::where('user_id', $sponsor->id)
                ->where('source', 'deposit')
                ->where('description', 'LIKE', '%DTEHM Referral Commission%')
                ->where('description', 'LIKE', '%Membership ID: ' . $membershipId . '%')
                ->first();

            if ($existingCommission) {
                \Log::warning('Commission already exists for this membership', [
                    'transaction_id' => $existingCommission->id,
                    'sponsor_id' => $sponsor->id,
                    'membership_id' => $membershipId,
                ]);
                return;
            }

            // Create commission transaction (10,000 UGX)
            $commission = \App\Models\AccountTransaction::create([
                'user_id' => $sponsor->id,
                'amount' => 10000,
                'transaction_date' => now(),
                'description' => "DTEHM Referral Commission: {$user->name} (Phone: {$user->phone_number}) paid DTEHM membership. Membership ID: {$membershipId}",
                'source' => 'deposit',
                'created_by_id' => \Admin::user() ? \Admin::user()->id : 1, // Fallback to admin ID 1
            ]);

            \Log::info('Sponsor commission created successfully', [
                'transaction_id' => $commission->id,
                'sponsor_id' => $sponsor->id,
                'sponsor_name' => $sponsor->name,
                'amount' => 10000,
                'referred_user' => $user->name,
                'membership_id' => $membershipId,
            ]);

            // Optional: Send notification to sponsor
            // $this->notifySponsorOfCommission($sponsor, $user, $commission);

        } catch (\Exception $e) {
            \Log::error('Failed to create sponsor commission', [
                'user_id' => $user->id,
                'sponsor_id' => $user->sponsor_id ?? 'NONE',
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
