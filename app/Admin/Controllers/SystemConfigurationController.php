<?php

namespace App\Admin\Controllers;

use App\Models\SystemConfiguration;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SystemConfigurationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'System Configuration';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SystemConfiguration());

        $grid->model()->where('id', 1); // Show only the record with ID 1
        $grid->column('company_name', __('Company name'));
        $grid->disableCreateButton(); // Disable the create button 
        $grid->column('company_email', __('Company email'));
        $grid->column('company_phone', __('Company phone'));
        $grid->column('company_pobox', __('Company pobox'))->hide();
        $grid->column('company_address', __('Company address'))->hide();
        $grid->column('company_website', __('Company website'))->hide();
        $grid->column('company_logo', __('Company logo'))
            ->lightbox(['width' => 100, 'height' => 100]);
        $grid->column('company_details', __('Company details'))->hide();
        $grid->column('insurance_start_date', __('Insurance start date'))->sortable();
        $grid->column('insurance_price', __('Insurance price'))->hide(); 

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
        $show = new Show(SystemConfiguration::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('company_name', __('Company name'));
        $show->field('company_email', __('Company email'));
        $show->field('company_phone', __('Company phone'));
        $show->field('company_pobox', __('Company pobox'));
        $show->field('company_address', __('Company address'));
        $show->field('company_website', __('Company website'));
        $show->field('company_logo', __('Company logo'));
        $show->field('company_details', __('Company details'));
        $show->field('insurance_start_date', __('Insurance start date'));
        $show->field('insurance_price', __('Insurance price'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SystemConfiguration());

        $form->tab('Company Info', function ($form) {
            $form->divider('Company Information');
            
            $form->text('company_name', __('Company Name'))
                ->required()
                ->help('Official company name');
            
            $form->email('company_email', __('Company Email'))
                ->help('Official email address');
            
            $form->text('company_phone', __('Company Phone'))
                ->help('Primary contact phone');
            
            $form->text('company_pobox', __('P.O. Box'))
                ->help('Postal address');
            
            $form->textarea('company_address', __('Physical Address'))
                ->rows(2)
                ->help('Complete physical address');
            
            $form->url('company_website', __('Website'))
                ->help('Company website URL');
            
            $form->image('company_logo', __('Company Logo'))
                ->help('Upload company logo (PNG/JPG)');
            
            $form->textarea('company_details', __('Company Details'))
                ->rows(4)
                ->help('About the company');
        });

        $form->tab('Membership Fees', function ($form) {
            $form->divider('Membership Fee Configuration');
            
            $form->currency('dtehm_membership_fee', __('DTEHM Membership Fee'))
                ->symbol('UGX')
                ->default(76000)
                ->required()
                ->help('Annual DTEHM membership fee (Full network marketing privileges)');
            
            $form->currency('dip_membership_fee', __('DIP Membership Fee'))
                ->symbol('UGX')
                ->default(20000)
                ->required()
                ->help('Annual DIP membership fee (Basic membership)');
            
            $form->select('currency', __('System Currency'))
                ->options([
                    'UGX' => 'Uganda Shilling (UGX)',
                    'USD' => 'US Dollar (USD)',
                    'EUR' => 'Euro (EUR)',
                    'GBP' => 'British Pound (GBP)',
                    'KES' => 'Kenyan Shilling (KES)',
                ])
                ->default('UGX')
                ->required()
                ->help('Primary currency for the system');
            
            $form->divider('Commission & Bonuses');
            
            $form->decimal('referral_bonus_percentage', __('Referral Bonus %'))
                ->default(5.00)
                ->help('Percentage bonus for referrals');
        });

        $form->tab('Insurance', function ($form) {
            $form->divider('Insurance Configuration');
            
            $form->datetime('insurance_start_date', __('Insurance Start Date'))
                ->default(date('Y-m-d H:i:s'))
                ->help('When insurance coverage begins');
            
            $form->currency('insurance_price', __('Insurance Price'))
                ->symbol('UGX')
                ->help('Monthly insurance subscription price');
        });

        $form->tab('Investment', function ($form) {
            $form->divider('Investment Configuration');
            
            $form->currency('minimum_investment_amount', __('Minimum Investment'))
                ->symbol('UGX')
                ->default(10000)
                ->help('Minimum amount for project investments');
            
            $form->currency('share_price', __('Share Price'))
                ->symbol('UGX')
                ->default(50000)
                ->help('Price per share for projects');
        });

        $form->tab('Payment Gateway', function ($form) {
            $form->divider('Payment Gateway Settings');
            
            $form->select('payment_gateway', __('Payment Gateway'))
                ->options([
                    'pesapal' => 'PesaPal',
                    'flutterwave' => 'Flutterwave',
                    'stripe' => 'Stripe',
                    'paypal' => 'PayPal',
                ])
                ->default('pesapal')
                ->required()
                ->help('Primary payment gateway');
            
            $form->url('payment_callback_url', __('Payment Callback URL'))
                ->help('URL for payment status callbacks');
        });

        $form->tab('App Settings', function ($form) {
            $form->divider('Mobile App Configuration');
            
            $form->text('app_version', __('Current App Version'))
                ->default('1.0.0')
                ->required()
                ->help('Current mobile app version (e.g., 1.0.0)');
            
            $form->switch('force_update', __('Force Update'))
                ->states([
                    'on' => ['value' => 1, 'text' => 'YES', 'color' => 'danger'],
                    'off' => ['value' => 0, 'text' => 'NO', 'color' => 'success'],
                ])
                ->help('Force users to update the app');
            
            $form->switch('maintenance_mode', __('Maintenance Mode'))
                ->states([
                    'on' => ['value' => 1, 'text' => 'ON', 'color' => 'warning'],
                    'off' => ['value' => 0, 'text' => 'OFF', 'color' => 'success'],
                ])
                ->help('Put app in maintenance mode');
            
            $form->textarea('maintenance_message', __('Maintenance Message'))
                ->rows(3)
                ->default('System is under maintenance. Please try again later.')
                ->help('Message shown during maintenance');
        });

        $form->tab('Contact Info', function ($form) {
            $form->divider('Contact Information');
            
            $form->text('contact_phone', __('Contact Phone'))
                ->help('Customer support phone number');
            
            $form->email('contact_email', __('Contact Email'))
                ->help('Customer support email');
            
            $form->textarea('contact_address', __('Contact Address'))
                ->rows(2)
                ->help('Customer support address');
            
            $form->divider('Social Media');
            
            $form->url('social_facebook', __('Facebook URL'))
                ->help('Facebook page URL');
            
            $form->url('social_twitter', __('Twitter/X URL'))
                ->help('Twitter/X profile URL');
            
            $form->url('social_instagram', __('Instagram URL'))
                ->help('Instagram profile URL');
            
            $form->url('social_linkedin', __('LinkedIn URL'))
                ->help('LinkedIn page URL');
        });

        $form->tab('Legal', function ($form) {
            $form->divider('Legal Documents');
            
            $form->textarea('terms_and_conditions', __('Terms & Conditions'))
                ->rows(10)
                ->help('Full terms and conditions text');
            
            $form->textarea('privacy_policy', __('Privacy Policy'))
                ->rows(10)
                ->help('Privacy policy text');
            
            $form->textarea('about_us', __('About Us'))
                ->rows(8)
                ->help('About the company/app');
        });

        $form->disableCreatingCheck();
        $form->disableViewCheck();
        $form->disableReset();
        
        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
        });

        return $form;
    }
}
