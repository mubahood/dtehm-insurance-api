<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Insurance Users';

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
        
        // Avatar Column
        $grid->column('avatar', __('Photo'))
            ->image('', 40, 40)
            ->width(60);
        
        // Full Name Column
        $grid->column('full_name', __('Full Name'))
            ->display(function () {
                return trim($this->first_name . ' ' . $this->last_name);
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
            ->width(180);
        
        // User Type Column
        $grid->column('user_type', __('User Type'))
            ->label([
                'Admin' => 'danger',
                'Customer' => 'success',
                'Vendor' => 'warning',
            ])
            ->filter([
                'Admin' => 'Admin',
                'Customer' => 'Customer',
                'Vendor' => 'Vendor',
            ])
            ->sortable()
            ->width(100);
        
        // Country Column
        $grid->column('country', __('Country'))
            ->width(120);
        
        // Tribe Column
        $grid->column('tribe', __('Tribe'))
            ->width(120);
        
        // Address Column
        $grid->column('address', __('Address'))
            ->limit(30)
            ->width(150);
        
        // Status Column
        $grid->column('status', __('Status'))
            ->label([
                'Active' => 'success',
                'Pending' => 'warning',
                'Banned' => 'danger',
                'Inactive' => 'default',
            ], 'Active')
            ->filter([
                'Active' => 'Active',
                'Pending' => 'Pending',
                'Banned' => 'Banned',
                'Inactive' => 'Inactive',
            ])
            ->width(90);
        
        // Date of Birth Column
        $grid->column('dob', __('DOB'))
            ->display(function ($dob) {
                if (empty($dob) || $dob == '0000-00-00') {
                    return '-';
                }
                return date('d M Y', strtotime($dob));
            })
            ->width(100);
        
        // Created At Column
        $grid->column('created_at', __('Registered'))
            ->display(function ($created_at) {
                return date('d M Y', strtotime($created_at));
            })
            ->sortable()
            ->width(100);
        
        // Quick Search
        $grid->quickSearch('first_name', 'last_name', 'email', 'phone_number')->placeholder('Search by name, email or phone');
        
        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            
            $filter->like('first_name', 'First Name');
            $filter->like('last_name', 'Last Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('email', 'Email');
            $filter->equal('sex', 'Gender')->radio([
                '' => 'All',
                'Male' => 'Male',
                'Female' => 'Female',
            ]);
            $filter->like('country', 'Country');
            $filter->like('tribe', 'Tribe');
            $filter->between('created_at', 'Registered Date')->date();
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
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());
        
        // SECTION 1: Basic Information
        $form->divider('Basic Information');
        
        $form->row(function ($row) {
            $row->width(6)->text('first_name', __('First Name'))
                ->rules('required')
                ->help('Required field');
            $row->width(6)->text('last_name', __('Last Name'))
                ->rules('required')
                ->help('Required field');
        });
        
        $form->row(function ($row) {
            $row->width(4)->radio('sex', __('Gender'))
                ->options([
                    'Male' => 'Male',
                    'Female' => 'Female',
                ])
                ->rules('required')
                ->default('Male');
            
            $row->width(4)->radio('user_type', __('User Type'))
                ->options([
                    'Customer' => 'Customer',
                    'Admin' => 'Admin',
                ])
                ->rules('required')
                ->default('Customer')
                ->help('Customer = Insurance User, Admin = System Administrator');
            
            $row->width(4)->date('dob', __('Date of Birth'))
                ->format('YYYY-MM-DD')
                ->help('Required field');
        });
        
        // SECTION 2: Contact Information
        $form->divider('Contact Information');
        
        $form->row(function ($row) {
            $row->width(6)->mobile('phone_number', __('Phone Number'))
                ->options(['mask' => '9999999999'])
                ->rules('required')
                ->help('Required field');
            
            $row->width(6)->email('email', __('Email Address'))
                ->help('Optional field');
        });
        
        // SECTION 3: Location Information
        $form->divider('Location Information');
        
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
        
        $form->row(function ($row) use ($countries, $tribes) {
            $row->width(6)->select('country', __('Country of Residence'))
                ->options($countries)
                ->default('Uganda')
                ->rules('required')
                ->help('Required field');
            
            $row->width(6)->select('tribe', __('Tribe'))
                ->options($tribes)
                ->help('Select your tribe');
        });
        
        $form->row(function ($row) {
            $row->width(6)->text('address', __('Home Address'))
                ->rules('required')
                ->help('Your permanent home address');
        });
        
        // SECTION 4: Family Information
        $form->divider('Family Information');
        
        $form->row(function ($row) {
            $row->width(6)->text('father_name', __("Father's Name"))
                ->rules('required')
                ->help('Required field');
            
            $row->width(6)->text('mother_name', __("Mother's Name"))
                ->rules('required')
                ->help('Required field');
        });
        
        // SECTION 5: Biological Children (Optional)
        $form->divider('Biological Children (if any)');
        
        $form->row(function ($row) {
            $row->width(6)->text('child_1', __('1st Child'))
                ->help('Full name of 1st child (optional)');
            
            $row->width(6)->text('child_2', __('2nd Child'))
                ->help('Full name of 2nd child (optional)');
        });
        
        $form->row(function ($row) {
            $row->width(6)->text('child_3', __('3rd Child'))
                ->help('Full name of 3rd child (optional)');
            
            $row->width(6)->text('child_4', __('4th Child'))
                ->help('Full name of 4th child (optional)');
        });
        
        // SECTION 6: Sponsor Information
        $form->divider('Sponsor Information (Optional)');
        
        $form->text('sponsor_id', __('Sponsor ID Number'))
            ->help('National ID of the person who sponsored you (optional)');
        
        // SECTION 7: Profile Photo
        $form->divider('Profile Photo');
        
        $form->image('avatar', __('Profile Photo'))
            ->help('Upload profile photo (optional)')
            ->uniqueName()
            ->move('images/users');
        
        // SECTION 8: Account Status & Password
        $form->divider('Account Status & Security');
        
        $form->row(function ($row) {
            $row->width(6)->radio('status', __('Account Status'))
                ->options([
                    'Active' => 'Active',
                    'Pending' => 'Pending',
                    'Inactive' => 'Inactive',
                    'Banned' => 'Banned',
                ])
                ->default('Active')
                ->rules('required');
        });
        
        $form->password('password', __('Password'))
            ->rules('nullable|confirmed|min:6')
            ->help('Leave blank to keep current password (when editing). Minimum 6 characters.')
            ->creationRules('required|min:6');
        
        $form->password('password_confirmation', __('Confirm Password'))
            ->rules('nullable|min:6')
            ->help('Re-enter password for confirmation');
        
        // Auto-generate name field from first_name and last_name
        $form->saving(function (Form $form) {
            if ($form->first_name && $form->last_name) {
                $form->name = trim($form->first_name . ' ' . $form->last_name);
            }
            
            // Hash password if provided
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = bcrypt($form->password);
            } else {
                // Remove password from update if not changed
                $form->model()->password = $form->model()->getOriginal('password');
            }
        });
        
        // Hide password confirmation from database
        $form->ignore(['password_confirmation']);
        
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
}
