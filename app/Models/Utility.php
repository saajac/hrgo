<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\CommonEmailTemplate;
use Carbon\Carbon;
use Spatie\GoogleCalendar\Event as GoogleEvent;


class Utility extends Model
{
    public static function settings()
    {
        $data = DB::table('settings');
        if (\Auth::check()) {
            $userId = \Auth::user()->creatorId();
            $data   = $data->where('created_by', '=', $userId);
        } else {
            $data = $data->where('created_by', '=', 1);
        }
        $data = $data->get();



        $settings = [
            "site_currency" => "Dollars",
            "site_currency_symbol" => "$",
            "site_currency_symbol_position" => "pre",
            "site_date_format" => "M j, Y",
            "site_time_format" => "g:i A",
            "company_name" => "",
            "company_address" => "",
            "company_city" => "",
            "company_state" => "",
            "company_zipcode" => "",
            "company_country" => "",
            "company_telephone" => "",
            "company_email" => "",
            "company_email_from_name" => "",
            "employee_prefix" => "#EMP00",
            "footer_title" => "",
            "footer_notes" => "",
            "company_start_time" => "09:00",
            "company_end_time" => "18:00",
            'new_user' => '1',
            'new_employee' => '1',
            'new_payroll' => '1',
            'new_ticket' => '1',
            'new_award' => '1',
            'employee_transfer' => '1',
            'employee_resignation' => '1',
            'employee_trip' => '1',
            'employee_promotion' => '1',
            'employee_complaints' => '1',
            'employee_warning' => '1',
            'employee_termination' => '1',
            'leave_status' => '1',
            'contract' => '1',
            "default_language" => "en",
            "display_landing_page" => "on",
            "ip_restrict" => "on",
            "title_text" => "",
            "footer_text" => "",
            "SITE_RTL" => "",
            "gdpr_cookie" => "",
            "cookie_text" => "",
            "metakeyword" => "",
            "metadesc" => "",
            "zoom_apikey" => "",
            "zoom_secret_key" => '',
            'disable_signup_button' => "",
            "dark_mode" => "",
            "theme_color" => 'theme-3',
            "is_sidebar_transperent" => 'on',
            "contract_prefix" => "#CON",
            "storage_setting" => "local",
            "local_storage_validation" => "jpg,jpeg,png,xlsx,xls,csv,pdf",
            "local_storage_max_upload_size" => "2048000",
            "s3_key" => "",
            "s3_secret" => "",
            "s3_region" => "",
            "s3_bucket" => "",
            "s3_url"    => "",
            "s3_endpoint" => "",
            "s3_max_upload_size" => "",
            "s3_storage_validation" => "",
            "wasabi_key" => "",
            "wasabi_secret" => "",
            "wasabi_region" => "",
            "wasabi_bucket" => "",
            "wasabi_url" => "",
            "wasabi_root" => "",
            "wasabi_max_upload_size" => "",
            "wasabi_storage_validation" => "",
            "google_clender_id" => "",
            "google_calender_json_file" => "",
            "is_enabled" => "",
            "meta_title" => "",
            "meta_image" => "",
            "meta_description" => "",
            'enable_cookie' => 'on',
            'necessary_cookies' => 'on',
            'cookie_logging' => 'on',
            'cookie_title' => 'We use cookies!',
            'cookie_description' => 'Hi, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it',
            'strictly_cookie_title' => 'Strictly necessary cookies',
            'strictly_cookie_description' => 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
            'more_information_description' => 'For any queries in relation to our policy on cookies and your choices, please contact us',
            'contactus_url' => '#',
            'chatgpt_key' => '',
            'enable_chatgpt' => '',
        ];

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    // public static function languages()
    // {
    //     $dir     = base_path() . '/resources/lang/';
    //     $glob    = glob($dir . "*", GLOB_ONLYDIR);
    //     $arrLang = array_map(
    //         function ($value) use ($dir) {
    //             return str_replace($dir, '', $value);
    //         },
    //         $glob
    //     );
    //     $arrLang = array_map(
    //         function ($value) use ($dir) {
    //             return preg_replace('/[0-9]+/', '', $value);
    //         },
    //         $arrLang
    //     );
    //     $arrLang = array_filter($arrLang);

    //     return $arrLang;
    // }

    public static function languages()
    {
        $languages = Utility::langList();

        if (\Schema::hasTable('languages')) {
            $settings = Utility::langSetting();
            if (!empty($settings['disable_lang'])) {
                $disabledlang = explode(',', $settings['disable_lang']);
                $languages = Languages::whereNotIn('code', $disabledlang)->pluck('fullName', 'code');
            } else {
                $languages = Languages::pluck('fullName', 'code');
            }
        }

        return $languages;
    }

    public static function getValByName($key)
    {
        $setting = Utility::settings();
        if (!isset($setting[$key]) || empty($setting[$key])) {
            $setting[$key] = '';
        }
        return $setting[$key];
    }

    public static function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str     = file_get_contents($envFile);
        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                $keyPosition       = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine           = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}='{$envValue}'\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}='{$envValue}'", $str);
                }
            }
        }
        $str = substr($str, 0, -1);
        $str .= "\n";
        if (!file_put_contents($envFile, $str)) {
            return false;
        }
        return true;
    }

    public static $emailStatus = [
        'new_user' => 'New Use',
        'new_employee' => 'New Employee',
        'new_payroll' => 'New Payroll',
        'new_ticket' => 'New Ticket',
        'new_award' => 'New Award',
        'employee_transfer' => 'Employee Transfer',
        'employee_resignation' => 'Employee Resignation',
        'employee_trip' => 'Employee Trip',
        'employee_promotion' => 'Employee Promotion',
        'employee_complaints' => 'Employee Complaints',
        'employee_warning' => 'Employee Warning',
        'employee_termination' => 'Employee Termination',
        'leave_status' => 'Leave Status',
        'contract' => 'Contract',
    ];

    public static function employeePayslipDetail($employeeId, $month)
    {
        // allowance
        $earning['allowance'] = PaySlip::where('employee_id', $employeeId)->where('salary_month', $month)->get();

        $employess = Employee::find($employeeId);

        $totalAllowance = 0;

        $arrayJson = json_decode($earning['allowance']);
        foreach ($arrayJson as $earn) {
            $allowancejson = json_decode($earn->allowance);
            foreach ($allowancejson as $allowances) {
                if ($allowances->type == 'percentage') {
                    $empall  = $allowances->amount * $earn->basic_salary / 100;
                } else {
                    $empall = $allowances->amount;
                }
                $totalAllowance += $empall;
            }
        }

        // commission
        $earning['commission'] = PaySlip::where('employee_id', $employeeId)->where('salary_month', $month)->get();

        $employess = Employee::find($employeeId);

        $totalCommission = 0;

        $arrayJson = json_decode($earning['commission']);

        foreach ($arrayJson as $earn) {
            $commissionjson = json_decode($earn->commission);

            foreach ($commissionjson as $commissions) {

                if ($commissions->type == 'percentage') {
                    $empcom  = $commissions->amount * $earn->basic_salary / 100;
                } else {
                    $empcom = $commissions->amount;
                }
                $totalCommission += $empcom;
            }
        }

        // otherpayment
        $earning['otherPayment']      = PaySlip::where('employee_id', $employeeId)->where('salary_month', $month)->get();

        $employess = Employee::find($employeeId);

        $totalotherpayment = 0;

        $arrayJson = json_decode($earning['otherPayment']);

        foreach ($arrayJson as $earn) {
            $otherpaymentjson = json_decode($earn->other_payment);

            foreach ($otherpaymentjson as $otherpay) {
                if ($otherpay->type == 'percentage') {
                    $empotherpay  = $otherpay->amount * $earn->basic_salary / 100;
                } else {
                    $empotherpay = $otherpay->amount;
                }
                $totalotherpayment += $empotherpay;
            }
        }

        //overtime
        $earning['overTime'] = Payslip::where('employee_id', $employeeId)->where('salary_month', $month)->get();

        $ot = 0;

        $arrayJson = json_decode($earning['overTime']);
        foreach ($arrayJson as $overtime) {
            $overtimes = json_decode($overtime->overtime);
            foreach ($overtimes as $overt) {
                $OverTime = $overt->number_of_days * $overt->hours * $overt->rate;
                $ot += $OverTime;
            }
        }

        // loan
        $deduction['loan'] = PaySlip::where('employee_id', $employeeId)->where('salary_month', $month)->get();

        $employess = Employee::find($employeeId);

        $totalloan = 0;

        $arrayJson = json_decode($deduction['loan']);

        foreach ($arrayJson as $loan) {
            $loans = json_decode($loan->loan);

            foreach ($loans as $emploans) {

                if ($emploans->type == 'percentage') {
                    $emploan  = $emploans->amount * $loan->basic_salary / 100;
                } else {
                    $emploan = $emploans->amount;
                }
                $totalloan += $emploan;
            }
        }

        // saturation_deduction
        $deduction['deduction']      = PaySlip::where('employee_id', $employeeId)->where('salary_month', $month)->get();

        $employess = Employee::find($employeeId);

        $totaldeduction = 0;

        $arrayJson = json_decode($deduction['deduction']);

        foreach ($arrayJson as $deductions) {
            $deduc = json_decode($deductions->saturation_deduction);
            foreach ($deduc as $deduction_option) {
                if ($deduction_option->type == 'percentage') {
                    $empdeduction  = $deduction_option->amount * $deductions->basic_salary / 100;
                } else {
                    $empdeduction = $deduction_option->amount;
                }
                $totaldeduction += $empdeduction;
            }
        }

        $payslip['earning']        = $earning;
        $payslip['totalEarning']   = $totalAllowance + $totalCommission + $totalotherpayment + $ot;
        $payslip['deduction']      = $deduction;
        $payslip['totalDeduction'] = $totalloan + $totaldeduction;

        return $payslip;
    }

    public static function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    public static function addNewData()
    {
        \Artisan::call('cache:forget spatie.permission.cache');
        \Artisan::call('cache:clear');
        $usr            = \Auth::user();
        $arrPermissions = [
            "Manage Job Category",
            "Create Job Category",
            "Edit Job Category",
            "Delete Job Category",
            "Manage Job Stage",
            "Create Job Stage",
            "Edit Job Stage",
            "Delete Job Stage",
            "Manage Job",
            "Create Job",
            "Edit Job",
            "Delete Job",
            "Show Job",
            "Manage Job Application",
            "Create Job Application",
            "Edit Job Application",
            "Delete Job Application",
            "Show Job Application",
            "Move Job Application",
            "Add Job Application Note",
            "Delete Job Application Note",
            "Add Job Application Skill",
            "Manage Job OnBoard",
            "Manage Custom Question",
            "Create Custom Question",
            "Edit Custom Question",
            "Delete Custom Question",
            "Manage Interview Schedule",
            "Create Interview Schedule",
            "Edit Interview Schedule",
            "Delete Interview Schedule",
            "Manage Career",
            "Create Webhook",
            "Edit Webhook",
            "Delete Webhook",
        ];
        foreach ($arrPermissions as $ap) {
            // check if permission is not created then create it.
            $permission = Permission::where('name', 'LIKE', $ap)->first();
            if (empty($permission)) {
                Permission::create(['name' => $ap]);
            }
        }
        $companyRole          = Role::where('name', 'LIKE', 'company')->first();
        $companyPermissions   = $companyRole->getPermissionNames()->toArray();
        $companyNewPermission = [
            "Manage Job Category",
            "Create Job Category",
            "Edit Job Category",
            "Delete Job Category",
            "Manage Job Stage",
            "Create Job Stage",
            "Edit Job Stage",
            "Delete Job Stage",
            "Manage Job",
            "Create Job",
            "Edit Job",
            "Delete Job",
            "Show Job",
            "Manage Job Application",
            "Create Job Application",
            "Edit Job Application",
            "Delete Job Application",
            "Show Job Application",
            "Move Job Application",
            "Add Job Application Note",
            "Delete Job Application Note",
            "Add Job Application Skill",
            "Manage Job OnBoard",
            "Manage Custom Question",
            "Create Custom Question",
            "Edit Custom Question",
            "Delete Custom Question",
            "Manage Interview Schedule",
            "Create Interview Schedule",
            "Edit Interview Schedule",
            "Delete Interview Schedule",
            "Manage Career",
            "Create Webhook",
            "Edit Webhook",
            "Delete Webhook",

        ];
        foreach ($companyNewPermission as $op) {
            // check if permission is not assign to owner then assign.
            if (!in_array($op, $companyPermissions)) {
                $permission = Permission::findByName($op);
                $companyRole->givePermissionTo($permission);
            }
        }
        $employeeRole          = Role::where('name', 'LIKE', 'employee')->first();
        $employeePermissions   = $employeeRole->getPermissionNames()->toArray();
        $employeeNewPermission = [
            'Manage Career',
        ];
        foreach ($employeeNewPermission as $op) {
            // check if permission is not assign to owner then assign.
            if (!in_array($op, $employeePermissions)) {
                $permission = Permission::findByName($op);
                $employeeRole->givePermissionTo($permission);
            }
        }
    }

    public static function jobStage($id)
    {
        $stages = [
            'Applied',
            'Phone Screen',
            'Interview',
            'Hired',
            'Rejected',
        ];
        foreach ($stages as $stage) {

            JobStage::create(
                [
                    'title' => $stage,
                    'created_by' => $id,
                ]
            );
        }
    }

    public static function sendEmailTemplate($emailTemplate, $mailTo, $obj)
    {
        $usr = \Auth::user();

        //Remove Current Login user Email don't send mail to them
        unset($mailTo[$usr->id]);

        $mailTo = array_values($mailTo);

        if ($usr->type != 'super admin') {
            // find template is exist or not in our record
            $template = EmailTemplate::where('slug', $emailTemplate)->first();
            if (isset($template) && !empty($template)) {
                // check template is active or not by company
                $is_active = UserEmailTemplate::where('template_id', '=', $template->id)->where('user_id', '=', $usr->creatorId())->first();

                if ($is_active->is_active == 1) {
                    $settings = self::settings();

                    // get email content language base
                    $content = EmailTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $usr->lang)->first();

                    $content->from = $template->from;

                    if (!empty($content->content)) {

                        $content->content = self::replaceVariable($content->content, $obj);
                        // send email
                        try {
                            Mail::to($mailTo)->send(new CommonEmailTemplate($content, $settings, $mailTo[0]));
                        } catch (\Exception $e) {
                            $error = __('E-Mail has been not sent due to SMTP configuration');
                        }

                        if (isset($error)) {
                            $arReturn = [
                                'is_success' => false,
                                'error' => $error,
                            ];
                        } else {
                            $arReturn = [
                                'is_success' => true,
                                'error' => false,
                            ];
                        }
                    } else {
                        $arReturn = [
                            'is_success' => false,
                            'error' => __('Mail not send, email is empty'),
                        ];
                    }

                    return $arReturn;
                } else {
                    return [
                        'is_success' => true,
                        'error' => false,
                    ];
                }
            } else {
                return [
                    'is_success' => false,
                    'error' => __('Mail not send, email not found'),
                ];
            }
        }
    }

    public static function replaceVariable($content, $obj)
    {
        $arrVariable = [
            '{email}',
            '{password}',

            '{app_name}',
            '{app_url}',

            '{employee_name}',
            '{employee_email}',
            '{employee_password}',
            '{employee_branch}',
            '{employee_department}',
            '{employee_designation}',

            '{name}',
            '{salary_month}',
            '{url}',

            '{ticket_title}',
            '{ticket_name}',
            '{ticket_code}',
            '{ticket_description}',
            '{award_name}',

            '{transfer_name}',
            '{transfer_date}',
            '{transfer_department}',
            '{transfer_branch}',
            '{transfer_description}',

            '{assign_user}',
            '{resignation_date}',

            '{employee_trip_name}',
            '{purpose_of_visit}',
            '{start_date}',
            '{end_date}',
            '{place_of_visit}',
            '{trip_description}',

            '{employee_promotion_name}',
            '{promotion_designation}',
            '{promotion_title}',
            '{promotion_date}',

            '{employee_complaints_name}',

            '{employee_warning_name}',
            '{warning_subject}',
            '{warning_description}',

            '{employee_termination_name}',
            '{notice_date}',
            '{termination_date}',
            '{termination_type}',

            '{leave_status_name}',
            '{leave_status}',
            '{leave_reason}',
            '{leave_start_date}',
            '{leave_end_date}',
            '{total_leave_days}',

            '{announcement_title}',
            '{branch_name}',

            '{year}',

            '{meeting_title}',
            '{date}',
            '{time}',

            '{occasion_name}',

            '{company_policy_name}',

            '{ticket_priority}',

            '{event_name}',

            '{contract_number}',
            '{user_name}',

        ];
        $arrValue    = [
            'email' => '-',
            'password' => '-',

            'app_name' => '-',
            'app_url' => '-',

            'employee_name' => '-',
            'employee_email' => '-',
            'employee_password' => '-',
            'employee_branch' => '-',
            'employee_department' => '-',
            'employee_designation' => '-',

            'name' => '-',
            'salary_month' => '-',
            'url' => '-',

            'ticket_title' => '-',
            'ticket_name' => '-',
            'ticket_code' => '-',
            'ticket_description' => '-',
            'award_name' => '-',

            'transfer_name' => '-',
            'transfer_date' => '-',
            'transfer_department' => '-',
            'transfer_branch' => '-',
            'transfer_description' => '-',

            'assign_user' => '-',
            'resignation_date' => '-',

            'employee_trip_name' => '-',
            'purpose_of_visit' => '-',
            'start_date' => '-',
            'end_date' => '-',
            'place_of_visit' => '-',
            'trip_description' => '-',

            'employee_promotion_name' => '-',
            'promotion_designation' => '-',
            'promotion_title' => '-',
            'promotion_date' => '-',

            'employee_complaints_name' => '-',

            'employee_warning_name' => '-',
            'warning_subject' => '-',
            'warning_description' => '-',

            'employee_termination_name' => '-',
            'notice_date' => '-',
            'termination_date' => '-',
            'termination_type' => '-',

            'leave_status_name' => '-',
            'leave_status' => '-',
            'leave_reason' => '-',
            'leave_start_date' => '-',
            'leave_end_date' => '-',
            'total_leave_days' => '-',

            'announcement_title' => '-',
            'branch_name' => '-',

            'year' => '-',

            'meeting_title' => '-',
            'date' => '-',
            'time' => '-',

            'occasion_name' => '-',

            'company_policy_name' => '-',

            'ticket_priority' => '-',

            'event_name' => '-',

            'contract_number' => '-',
            'user_name' => '-',

        ];

        foreach ($obj as $key => $val) {
            $arrValue[$key] = $val;
        }
        $settings = Utility::settings();
        $company_name = $settings['company_name'];

        $arrValue['app_name']     = env('APP_NAME');
        $arrValue['company_name'] = self::settings()['company_name'];
        $arrValue['app_url']      = '<a href="' . env('APP_URL') . '" target="_blank">' . env('APP_URL') . '</a>';

        return str_replace($arrVariable, array_values($arrValue), $content);
    }

    public static function makeEmailLang($lang)
    {
        $template = EmailTemplate::all();
        foreach ($template as $t) {
            $default_lang                 = EmailTemplateLang::where('parent_id', '=', $t->id)->where('lang', 'LIKE', 'en')->first();
            $emailTemplateLang            = new EmailTemplateLang();
            $emailTemplateLang->parent_id = $t->id;
            $emailTemplateLang->lang      = $lang;
            $emailTemplateLang->subject   = $default_lang->subject;
            $emailTemplateLang->content   = $default_lang->content;
            $emailTemplateLang->save();
        }
    }
    public static function add_landing_page_data()
    {
        $section_data   = [];
        $section_data[] = [
            'section_name' => 'section-1',
            'section_order' => 1,
            'default_content' => '{"logo":"landing_logo.png","image":"top-banner.png","button":{"text":"Login"},"menu":[{"menu":"Features","href":"#"},{"menu":"Pricing","href":"#"}],"text":{"text-1":"HRMGo","text-2":"HRM and Payroll Tool","text-3":"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.","text-4":"get started - its free","text-5":"no creadit card reqired "},"custom_class_name":""}',
            'content' => '{"logo":"landing_logo.png","image":"top-banner.png","button":{"text":"Login"},"menu":[{"menu":"Features","href":"#"},{"menu":"Pricing","href":"#"}],"text":{"text-1":"HRMGo","text-2":"HRM and Payroll Tool","text-3":"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.","text-4":"get started - its free","text-5":"no creadit card reqired"},"custom_class_name":""}',
            'section_demo_image' => 'top-header-section.png',
            'section_blade_file_name' => 'custome-top-header-section',
            'section_type' => 'section-1',
        ];
        $section_data[] = [
            'section_name' => 'section-2',
            'section_order' => 2,
            'default_content' => '{"image":"cal-sec.png","button":{"text":"try our system","href":"#"},"text":{"text-1":"Features","text-2":"Lorem Ipsum is simply dummy","text-3":"text of the printing","text-4":"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting"},"image_array":[{"id":1,"image":"nexo.png"},{"id":2,"image":"edge.png"},{"id":3,"image":"atomic.png"},{"id":4,"image":"brd.png"},{"id":5,"image":"trust.png"},{"id":6,"image":"keep-key.png"},{"id":7,"image":"atomic.png"},{"id":8,"image":"edge.png"}],"custom_class_name":""}',
            'content' => '{"image":"cal-sec.png","button":{"text":"try our system","href":"#"},"text":{"text-1":"Features","text-2":"Lorem Ipsum is simply dummy","text-3":"text of the printing","text-4":"Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting"},"image_array":[{"id":1,"image":"nexo.png"},{"id":2,"image":"edge.png"},{"id":3,"image":"atomic.png"},{"id":4,"image":"brd.png"},{"id":5,"image":"trust.png"},{"id":6,"image":"keep-key.png"},{"id":7,"image":"atomic.png"},{"id":8,"image":"edge.png"}],"custom_class_name":""}',
            'section_demo_image' => 'logo-part-main-back-part.png',
            'section_blade_file_name' => 'custome-logo-part-main-back-part',
            'section_type' => 'section-2',
        ];
        $section_data[] = [
            'section_name' => 'section-3',
            'section_order' => 3,
            'default_content' => '{"image": "sec-2.png","button": {"text": "try our system","href": "#"},"text": {"text-1": "Features","text-2": "Lorem Ipsum is simply dummy","text-3": "text of the printing","text-4": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting"},"custom_class_name":""}',
            'section_demo_image' => 'simple-sec-even.png',
            'section_blade_file_name' => 'custome-simple-sec-even',
            'section_type' => 'section-3',
        ];
        $section_data[] = [
            'section_name' => 'section-4',
            'section_order' => 4,
            'default_content' => '{"image": "sec-3.png","button": {"text": "try our system","href": "#"},"text": {"text-1": "Features","text-2": "Lorem Ipsum is simply dummy","text-3": "text of the printing","text-4": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting"},"custom_class_name":""}',
            'section_demo_image' => 'simple-sec-odd.png',
            'section_blade_file_name' => 'custome-simple-sec-odd',
            'section_type' => 'section-4',
        ];
        $section_data[] = [
            'section_name' => 'section-5',
            'section_order' => 5,
            'default_content' => '{"button": {"text": "TRY OUR SYSTEM","href": "#"},"text": {"text-1": "See more features","text-2": "All Features","text-3": "in one place","text-4": "Attractive Dashboard Customer & Vendor Login Multi Languages","text-5":"Invoice, Billing & Transaction Multi User & Permission Paypal & Stripe for Invoice User Friendly Invoice Theme Make your own setting","text-6":"Multi User & Permission Paypal & Stripe for Invoice User Friendly Invoice Theme Make your own setting","text-7":"Multi User & Permission Paypal & Stripe for Invoice User Friendly Invoice Theme Make your own setting User Friendly Invoice Theme Make your own setting","text-8":"Multi User & Permission Paypal & Stripe for Invoice"},"custom_class_name":""}',
            'section_demo_image' => 'features-inner-part.png',
            'section_blade_file_name' => 'custome-features-inner-part',
            'section_type' => 'section-5',
        ];
        $section_data[] = [
            'section_name' => 'section-6',
            'section_order' => 6,
            'default_content' => '{"system":[{"id":1,"name":"Dashboard","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"},{"data_id":3,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-3.png"},{"data_id":4,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":5,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"}]},{"id":2,"name":"Functions","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"},{"data_id":3,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-3.png"}]},{"id":3,"name":"Reports","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"}]},{"id":4,"name":"Tables","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"},{"data_id":3,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-3.png"},{"data_id":4,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"}]},{"id":5,"name":"Settings","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"}]},{"id":6,"name":"Contact","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"}]}],"custom_class_name":""}',
            'content' => '{"system":[{"id":1,"name":"Dashboard","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"},{"data_id":3,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-3.png"},{"data_id":4,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":5,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"}]},{"id":2,"name":"Functions","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"},{"data_id":3,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-3.png"}]},{"id":3,"name":"Reports","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"}]},{"id":4,"name":"Tables","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"},{"data_id":3,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-3.png"},{"data_id":4,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"}]},{"id":5,"name":"Settings","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"},{"data_id":2,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-2.png"}]},{"id":6,"name":"Contact","data":[{"data_id":1,"text":{"text_1":"Dashboard","text_2":"Main Page"},"button":{"text":"LIVE DEMO","href":"#"},"image":"tab-1.png"}]}],"custom_class_name":""}',
            'section_demo_image' => 'container-our-system-div.png',
            'section_blade_file_name' => 'custome-container-our-system-div',
            'section_type' => 'section-6',
        ];
        $section_data[] = [
            'section_name' => 'section-7',
            'section_order' => 7,
            'default_content' => '{"testimonials":[{"id":1,"text":{"text_1":"We have been building AI projects for a long time and we decided it was time to build a platform that can streamline the broken process that we had to put up with. Here are some of the key things we wish we had when we were building projects before.","text_2":"Lorem Ipsum","text_3":"Founder and CEO at Rajodiya Infotech"},"image":"testimonials-img.png"},{"id":2,"text":{"text_1":"We have been building AI projects for a long time and we decided it was time to build a platform that can streamline the broken process that we had to put up with. Here are some of the key things we wish we had when we were building projects before.","text_2":"Lorem Ipsum","text_3":"Founder and CEO at Rajodiya Infotech"},"image":"testimonials-img.png"},{"id":3,"text":{"text_1":"We have been building AI projects for a long time and we decided it was time to build a platform that can streamline the broken process that we had to put up with. Here are some of the key things we wish we had when we were building projects before.","text_2":"Lorem Ipsum","text_3":"Founder and CEO at Rajodiya Infotech"},"image":"testimonials-img.png"},{"id":4,"text":{"text_1":"We have been building AI projects for a long time and we decided it was time to build a platform that can streamline the broken process that we had to put up with. Here are some of the key things we wish we had when we were building projects before.","text_2":"Lorem Ipsum","text_3":"Founder and CEO at Rajodiya Infotech"},"image":"testimonials-img.png"},{"id":5,"text":{"text_1":"We have been building AI projects for a long time and we decided it was time to build a platform that can streamline the broken process that we had to put up with. Here are some of the key things we wish we had when we were building projects before.","text_2":"Lorem Ipsum","text_3":"Founder and CEO at Rajodiya Infotech"},"image":"testimonials-img.png"}],"custom_class_name":""}',
            'section_demo_image' => 'testimonials-section.png',
            'section_blade_file_name' => 'custome-testimonials-section',
            'section_type' => 'section-7',
        ];

        $section_data[] = [
            'section_name' => 'section-8',
            'section_order' => 9,
            'default_content' => '{"button":{"text":"Subscribe"},"text":{"text-1":"Try for free","text-2":"Lorem Ipsum is simply dummy text","text-3":"of the printing and typesetting industry","text-4":"Type your email address and click the button"},"custom_class_name":""}',
            'content' => '{"button":{"text":"Subscribe"},"text":{"text-1":"Try for free","text-2":"Lorem Ipsum is simply dummy text","text-3":"of the printing and typesetting industry","text-4":"Type your email address and click the button"},"custom_class_name":""}',
            'section_demo_image' => 'subscribe-part.png',
            'section_blade_file_name' => 'custome-subscribe-part',
            'section_type' => 'section-8',
        ];
        $section_data[] = [
            'section_name' => 'section-9',
            'section_order' => 10,
            'default_content' => '{"menu":[{"menu":"Facebook","href":"#"},{"menu":"LinkedIn","href":"#"},{"menu":"Twitter","href":"#"},{"menu":"Discord","href":"#"}],"custom_class_name":""}',
            'content' => '{"menu":[{"menu":"Facebook","href":"#"},{"menu":"LinkedIn","href":"#"},{"menu":"Twitter","href":"#"},{"menu":"Discord","href":"#"}],"custom_class_name":""}',
            'section_demo_image' => 'social-links.png',
            'section_blade_file_name' => 'custome-social-links',
            'section_type' => 'section-9',
        ];
        $section_data[] = [
            'section_name' => 'section-10',
            'section_order' => 11,
            'default_content' => '{"footer":{"logo":{"logo":"landing_logo.png"},"footer_menu":[{"id":1,"menu":"FIO Protocol","data":[{"menu_name":"Feature","menu_href":"#"},{"menu_name":"Download","menu_href":"#"},{"menu_name":"Integration","menu_href":"#"},{"menu_name":"Extras","menu_href":"#"}]},{"id":2,"menu":"Learn","data":[{"menu_name":"Feature","menu_href":"#"},{"menu_name":"Download","menu_href":"#"},{"menu_name":"Integration","menu_href":"#"},{"menu_name":"Extras","menu_href":"#"}]},{"id":3,"menu":"Foundation","data":[{"menu_name":"About Us","menu_href":"#"},{"menu_name":"Customers","menu_href":"#"},{"menu_name":"Resources","menu_href":"#"},{"menu_name":"Blog","menu_href":"#"}]}],"contact_app":[{"menu":"Contact","data":[{"id":1,"image":"app-store.png","image_href":"#"},{"id":2,"image":"google-pay.png","image_href":"#"}]}],"bottom_menu":{"text":"All rights reserved.","data":[{"menu_name":"Privacy Policy","menu_href":"#"},{"menu_name":"Github","menu_href":"#"},{"menu_name":"Press Kit","menu_href":"#"},{"menu_name":"Contact","menu_href":"#"}]}},"custom_class_name":""}',
            'content' => '{"footer":{"logo":{"logo":"landing_logo.png"},"footer_menu":[{"id":1,"menu":"FIO Protocol","data":[{"menu_name":"Feature","menu_href":"#"},{"menu_name":"Download","menu_href":"#"},{"menu_name":"Integration","menu_href":"#"},{"menu_name":"Extras","menu_href":"#"}]},{"id":2,"menu":"Learn","data":[{"menu_name":"Feature","menu_href":"#"},{"menu_name":"Download","menu_href":"#"},{"menu_name":"Integration","menu_href":"#"},{"menu_name":"Extras","menu_href":"#"}]},{"id":3,"menu":"Foundation","data":[{"menu_name":"About Us","menu_href":"#"},{"menu_name":"Customers","menu_href":"#"},{"menu_name":"Resources","menu_href":"#"},{"menu_name":"Blog","menu_href":"#"}]}],"contact_app":[{"menu":"Contact","data":[{"id":1,"image":"app-store.png","image_href":"#"},{"id":2,"image":"google-pay.png","image_href":"#"}]}],"bottom_menu":{"text":"All rights reserved.","data":[{"menu_name":"Privacy Policy","menu_href":"#"},{"menu_name":"Github","menu_href":"#"},{"menu_name":"Press Kit","menu_href":"#"},{"menu_name":"Contact","menu_href":"#"}]}},"custom_class_name":""}',
            'section_demo_image' => 'footer-section.png',
            'section_blade_file_name' => 'custome-footer-section',
            'section_type' => 'section-10',
        ];


        foreach ($section_data as $section_key => $section_value) {

            LandingPageSection::create($section_value);
        }

        return true;
    }
    public static function getProgressColor($percentage)
    {
        $color = '';
        if ($percentage <= 20) {
            $color = 'danger';
        } elseif ($percentage > 20 && $percentage <= 40) {
            $color = 'warning';
        } elseif ($percentage > 40 && $percentage <= 60) {
            $color = 'info';
        } elseif ($percentage > 60 && $percentage <= 80) {
            $color = 'primary';
        } elseif ($percentage >= 80) {
            $color = 'success';
        }
        return $color;
    }

    public static function getselectedThemeColor()
    {
        $color = env('THEME_COLOR');
        if ($color == "" || $color == null) {
            $color = 'blue';
        }
        return $color;
    }

    public static function getAllThemeColors()
    {
        $colors = [
            'blue', 'denim', 'sapphire', 'olympic', 'violet', 'black', 'cyan', 'dark-blue-natural', 'gray-dark', 'light-blue', 'light-purple', 'magenta', 'orange-mute', 'pale-green', 'rich-magenta', 'rich-red', 'sky-gray'
        ];
        return $colors;
    }

    public static function send_slack_msg($slug, $obj)
    {
        $notification_template = NotificationTemplates::where('slug', $slug)->first();
        if (!empty($notification_template) && !empty($obj)) {
            $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', \Auth::user()->lang)->where('created_by', '=', \Auth::user()->creatorId())->first();
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', \Auth::user()->lang)->first();
            }
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang       = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', 'en')->first();
            }
            if (!empty($curr_noti_tempLang) && !empty($curr_noti_tempLang->content)) {
                $msg = self::replaceVariable($curr_noti_tempLang->content, $obj);
            }
        }
        if (isset($msg)) {
            $settings  = Utility::settings(\Auth::user()->creatorId());
            try {
                if (isset($settings['slack_webhook']) && !empty($settings['slack_webhook'])) {
                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, $settings['slack_webhook']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $msg]));

                    $headers = array();
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    $result = curl_exec($ch);
                    if (curl_errno($ch)) {
                        echo 'Error:' . curl_error($ch);
                    }
                    curl_close($ch);
                }
            } catch (\Exception $e) {
            }
        }
    }

    public static function send_telegram_msg($slug, $obj)
    {
        $notification_template = NotificationTemplates::where('slug', $slug)->first();
        if (!empty($notification_template) && !empty($obj)) {
            $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', \Auth::user()->lang)->where('created_by', '=', \Auth::user()->creatorId())->first();
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', \Auth::user()->lang)->first();
            }
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang       = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', 'en')->first();
            }
            if (!empty($curr_noti_tempLang) && !empty($curr_noti_tempLang->content)) {
                $msg = self::replaceVariable($curr_noti_tempLang->content, $obj);
            }
        }
        if (isset($msg)) {
            $settings  = Utility::settings(\Auth::user()->creatorId());
            try {
                $msg = $msg;
                // Set your Bot ID and Chat ID.
                $telegrambot    = $settings['telegram_accestoken'];
                $telegramchatid = $settings['telegram_chatid'];
                // Function call with your own text or variable
                $url     = 'https://api.telegram.org/bot' . $telegrambot . '/sendMessage';
                $data    = array(
                    'chat_id' => $telegramchatid,
                    'text' => $msg,
                );
                $options = array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                        'content' => http_build_query($data),
                    ),
                );
                $context = stream_context_create($options);
                $result  = file_get_contents($url, false, $context);
                $url     = $url;
            } catch (\Exception $e) {
            }
        }
    }

    public static function send_twilio_msg($to, $slug, $obj)
    {
        $notification_template = NotificationTemplates::where('slug', $slug)->first();
        if (!empty($notification_template) && !empty($obj)) {
            $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', \Auth::user()->lang)->where('created_by', '=', \Auth::user()->creatorId())->first();
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', \Auth::user()->lang)->first();
            }
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang       = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', 'en')->first();
            }
            if (!empty($curr_noti_tempLang) && !empty($curr_noti_tempLang->content)) {
                $msg = self::replaceVariable($curr_noti_tempLang->content, $obj);
            }
        }
        if (isset($msg)) {
            $settings  = Utility::settings(\Auth::user()->creatorId());
            try {

                $account_sid    = $settings['twilio_sid'];
                $auth_token = $settings['twilio_token'];
                $twilio_number = $settings['twilio_from'];
                $client = new Client($account_sid, $auth_token);
                $client->messages->create($to, [
                    'from' => $twilio_number,
                    'body' => $msg
                ]);
            } catch (\Exception $e) {
            }
        }
    }

    public static function colorset()
    {

        if (\Auth::user()) {
            $user = \Auth::user()->id;
            $setting = DB::table('settings')->where('created_by', $user)->pluck('value', 'name')->toArray();
        } else {
            $setting = DB::table('settings')->pluck('value', 'name')->toArray();
        }
        return $setting;

        $is_dark_mode = $setting['dark_mode'];

        if ($is_dark_mode == 'on') {
            return 'logo-light.png';
        } else {
            return 'logo-dark.png';
        }
    }

    public static function mode_layout()
    {
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', 1);
        $data     = $data->get();
        $settings = [
            "dark_mode" => "off",
            "is_sidebar_transperent" => "on",
            "color" => 'theme-3'
        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function get_superadmin_logo()
    {
        $is_dark_mode = DB::table('settings')->where('created_by', '1')->pluck('value', 'name')->toArray();
        if (!empty($is_dark_mode['dark_mode'])) {
            $is_dark_modes = $is_dark_mode['dark_mode'];

            if ($is_dark_modes == 'on') {
                return 'light_logo.png';
            } else {
                return 'dark_logo.png';
            }
        } else {
            return 'dark_logo.png';
        }
    }
    public static function getTargetrating($designationid, $competencyCount)
    {
        $indicator = Indicator::where('designation', $designationid)->first();

        if (!empty($indicator->rating) && ($competencyCount != 0)) {
            $rating = json_decode($indicator->rating, true);
            $starsum = array_sum($rating);

            $overallrating = $starsum / $competencyCount;
        } else {
            $overallrating = 0;
        }
        return $overallrating;
    }

    public static function upload_file($request, $key_name, $name, $path, $custom_validation = [])
    {
        try {
            $settings = Utility::settings();

            if (!empty($settings['storage_setting'])) {

                if ($settings['storage_setting'] == 'wasabi') {

                    config(
                        [
                            'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                            'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                            'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                            'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com'
                        ]
                    );

                    $max_size = !empty($settings['wasabi_max_upload_size']) ? $settings['wasabi_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['wasabi_storage_validation']) ? $settings['wasabi_storage_validation'] : '';
                } else if ($settings['storage_setting'] == 's3') {
                    config(
                        [
                            'filesystems.disks.s3.key' => $settings['s3_key'],
                            'filesystems.disks.s3.secret' => $settings['s3_secret'],
                            'filesystems.disks.s3.region' => $settings['s3_region'],
                            'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                            'filesystems.disks.s3.use_path_style_endpoint' => false,
                        ]
                    );
                    $max_size = !empty($settings['s3_max_upload_size']) ? $settings['s3_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['s3_storage_validation']) ? $settings['s3_storage_validation'] : '';
                } else {
                    $max_size = !empty($settings['local_storage_max_upload_size']) ? $settings['local_storage_max_upload_size'] : '2048';

                    $mimes =  !empty($settings['local_storage_validation']) ? $settings['local_storage_validation'] : '';
                }


                $file = $request->$key_name;


                if (count($custom_validation) > 0) {
                    $validation = $custom_validation;
                } else {

                    $validation = [
                        'mimes:' . $mimes,
                        'max:' . $max_size,
                    ];
                }
                $validator = \Validator::make($request->all(), [
                    $key_name => $validation
                ]);
                if ($validator->fails()) {

                    $res = [
                        'flag' => 0,
                        'msg' => $validator->messages()->first(),
                    ];
                    return $res;
                } else {

                    $name = $name;

                    if ($settings['storage_setting'] == 'local') {
                        $request->$key_name->move(storage_path($path), $name);
                        $path = $path . $name;
                    } else if ($settings['storage_setting'] == 'wasabi') {

                        $path = \Storage::disk('wasabi')->putFileAs(
                            $path,
                            $file,
                            $name
                        );

                        // $path = $path.$name;

                    } else if ($settings['storage_setting'] == 's3') {

                        $path = \Storage::disk('s3')->putFileAs(
                            $path,
                            $file,
                            $name
                        );
                        // $path = $path.$name;
                    }


                    $res = [
                        'flag' => 1,
                        'msg'  => 'success',
                        'url'  => $path
                    ];
                    return $res;
                }
            } else {
                $res = [
                    'flag' => 0,
                    'msg' => __('Please set proper configuration for storage.'),
                ];
                return $res;
            }
        } catch (\Exception $e) {
            $res = [
                'flag' => 0,
                'msg' => $e->getMessage(),
            ];
            return $res;
        }
    }


    public static function get_file($path)
    {
        $settings = Utility::settings();

        try {
            if ($settings['storage_setting'] == 'wasabi') {
                config(
                    [
                        'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                        'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                        'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                        'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                        'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com'
                    ]
                );
            } elseif ($settings['storage_setting'] == 's3') {
                config(
                    [
                        'filesystems.disks.s3.key' => $settings['s3_key'],
                        'filesystems.disks.s3.secret' => $settings['s3_secret'],
                        'filesystems.disks.s3.region' => $settings['s3_region'],
                        'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                        'filesystems.disks.s3.use_path_style_endpoint' => false,
                    ]
                );
            }

            return \Storage::disk($settings['storage_setting'])->url($path);
        } catch (\Throwable $th) {
            return '';
        }
    }
    public static function upload_coustom_file($request, $key_name, $name, $path, $data_key, $custom_validation = [])
    {
        $multifile = [
            $key_name => $request->file($key_name)[$data_key],
        ];
        try {
            $settings = Utility::settings();


            if (!empty($settings['storage_setting'])) {

                if ($settings['storage_setting'] == 'wasabi') {

                    config(
                        [
                            'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                            'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                            'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                            'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com'
                        ]
                    );

                    $max_size = !empty($settings['wasabi_max_upload_size']) ? $settings['wasabi_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['wasabi_storage_validation']) ? $settings['wasabi_storage_validation'] : '';
                } else if ($settings['storage_setting'] == 's3') {
                    config(
                        [
                            'filesystems.disks.s3.key' => $settings['s3_key'],
                            'filesystems.disks.s3.secret' => $settings['s3_secret'],
                            'filesystems.disks.s3.region' => $settings['s3_region'],
                            'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                            'filesystems.disks.s3.use_path_style_endpoint' => false,
                        ]
                    );
                    $max_size = !empty($settings['s3_max_upload_size']) ? $settings['s3_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['s3_storage_validation']) ? $settings['s3_storage_validation'] : '';
                } else {
                    $max_size = !empty($settings['local_storage_max_upload_size']) ? $settings['local_storage_max_upload_size'] : '2048';

                    $mimes =  !empty($settings['local_storage_validation']) ? $settings['local_storage_validation'] : '';
                }


                $file = $request->$key_name;


                if (count($custom_validation) > 0) {
                    $validation = $custom_validation;
                } else {

                    $validation = [
                        'mimes:' . $mimes,
                        'max:' . $max_size,
                    ];
                }
                $validator = \Validator::make($multifile, [
                    $key_name => $validation
                ]);

                if ($validator->fails()) {
                    $res = [
                        'flag' => 0,
                        'msg' => $validator->messages()->first(),
                    ];
                    return $res;
                } else {

                    $name = $name;

                    if ($settings['storage_setting'] == 'local') {



                        \Storage::disk()->putFileAs(
                            $path,
                            $request->file($key_name)[$data_key],
                            $name
                        );


                        $path = $name;
                    } else if ($settings['storage_setting'] == 'wasabi') {

                        \Storage::disk('wasabi')->putFileAs(
                            $path,
                            $request->file($key_name)[$data_key],
                            $name
                        );
                        $path = $name;
                    } else if ($settings['storage_setting'] == 's3') {

                        \Storage::disk('s3')->putFileAs(
                            $path,
                            $request->file($key_name)[$data_key],
                            $name
                        );
                        $path = $name;
                    }

                    $res = [
                        'flag' => 1,
                        'msg'  => 'success',
                        'url'  => $path
                    ];
                    return $res;
                }
            } else {
                $res = [
                    'flag' => 0,
                    'msg' => __('Please set proper configuration for storage.'),
                ];
                return $res;
            }
        } catch (\Exception $e) {
            $res = [
                'flag' => 0,
                'msg' => $e->getMessage(),
            ];
            return $res;
        }
    }

    public static function colorCodeData($type)
    {
        if ($type == 'event') {
            return 1;
        } elseif ($type == 'zoom_meeting') {
            return 2;
        } elseif ($type == 'task') {
            return 3;
        } elseif ($type == 'appointment') {
            return 11;
        } elseif ($type == 'rotas') {
            return 3;
        } elseif ($type == 'holiday') {
            return 4;
        } elseif ($type == 'call') {
            return 10;
        } elseif ($type == 'meeting') {
            return 5;
        } elseif ($type == 'leave') {
            return 6;
        } elseif ($type == 'work_order') {
            return 7;
        } elseif ($type == 'lead') {
            return 7;
        } elseif ($type == 'deal') {
            return 8;
        } elseif ($type == 'interview_schedule') {
            return 9;
        } else {
            return 11;
        }
    }

    public static $colorCode = [
        1 => 'event-warning',
        2 => 'event-secondary',
        3 => 'event-success',
        4 => 'event-warning',
        5 => 'event-danger',
        6 => 'event-dark',
        7 => 'event-black',
        8 => 'event-info',
        9 => 'event-secondary',
        10 => 'event-success',
        11 => 'event-warning',
    ];

    public static function googleCalendarConfig()
    {
        $setting = Utility::settings();

        // $path = storage_path('app/google-calendar/' . $setting['google_calender_json_file']);
        $path = storage_path($setting['google_calender_json_file']);

        config([
            'google-calendar.default_auth_profile' => 'service_account',
            'google-calendar.auth_profiles.service_account.credentials_json' => $path,
            'google-calendar.auth_profiles.oauth.credentials_json' => $path,
            'google-calendar.auth_profiles.oauth.token_json' => $path,
            'google-calendar.calendar_id' => isset($setting['google_clender_id']) ? $setting['google_clender_id'] : '',
            'google-calendar.user_to_impersonate' => '',

        ]);
    }

    public static function addCalendarDataTime($request, $type)
    {
        Self::googleCalendarConfig();
        $event = new GoogleEvent();
        $event->name = $request->title;
        $date = $request->start_date  .  $request->time;
        $event->startDateTime = Carbon::createFromFormat('Y-m-d H:i', $date);
        $event->endDateTime = Carbon::createFromFormat('Y-m-d H:i', $date);
        $event->colorId = Self::colorCodeData($type);
        $event->save();
    }

    public static function addCalendarData($request, $type)
    {
        Self::googleCalendarConfig();

        $event = new GoogleEvent();
        $event->name = $request->title;
        $event->startDateTime = Carbon::parse($request->start_date);
        $event->endDateTime = Carbon::parse($request->end_date);
        $event->colorId = Self::colorCodeData($type);
        $event->save();
    }

    public static function getCalendarData($type)
    {
        Self::googleCalendarConfig();

        $data = GoogleEvent::get();
        $type = Self::colorCodeData($type);
        $arrayJson = [];
        foreach ($data as $val) {
            $end_date = date_create($val->endDateTime);

            date_add($end_date, date_interval_create_from_date_string("1 days"));

            if ($val->colorId == "$type") {
                $arrayJson[] = [
                    "id" => $val->id,
                    "title" => $val->summary,
                    "start" => $val->startDateTime,
                    "end" => date_format($end_date, "Y-m-d H:i:s"),
                    "className" => Self::$colorCode[$type],
                    "allDay" => true,
                ];
            }
        }
        return $arrayJson;
    }

    public static function getSeoSetting()
    {
        $data = \DB::table('settings')->whereIn('name', ['meta_title', 'meta_description', 'meta_image'])->get();
        $settings = [];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function webhookSetting($module)
    {
        $webhook = Webhook::where('module', $module)->where('created_by', '=', \Auth::user()->creatorId())->first();
        if (!empty($webhook)) {
            $url = $webhook->url;
            $method = $webhook->method;
            $reference_url  = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

            $data['method'] = $method;
            $data['reference_url'] = $reference_url;
            $data['url'] = $url;
            return $data;
        }
        return false;
    }

    public static function WebhookCall($url = null, $parameter = null, $method = 'POST')
    {

        if (!empty($url) && !empty($parameter)) {
            try {

                $curlHandle = curl_init($url);
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $parameter);
                curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                $curlResponse = curl_exec($curlHandle);
                curl_close($curlHandle);
                if (empty($curlResponse)) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Throwable $th) {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function getCookieSetting()
    {
        $data = \DB::table('settings')->whereIn('name', [
            'enable_cookie', 'cookie_logging', 'cookie_title',
            'cookie_description', 'necessary_cookies', 'strictly_cookie_title',
            'strictly_cookie_description', 'more_information_description', 'contactus_url'
        ])->get();
        $settings = [
            'enable_cookie' => 'off',
            'necessary_cookies' => '',
            'cookie_logging' => '',
            'cookie_title' => '',
            'cookie_description' => '',
            'strictly_cookie_title' => '',
            'strictly_cookie_description' => '',
            'more_information_description' => '',
            'contactus_url' => '',
        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function get_device_type($user_agent)
    {
        $mobile_regex = '/(?:phone|windows\s+phone|ipod|blackberry|(?:android|bb\d+|meego|silk|googlebot) .+? mobile|palm|windows\s+ce|opera mini|avantgo|mobilesafari|docomo)/i';
        $tablet_regex = '/(?:ipad|playbook|(?:android|bb\d+|meego|silk)(?! .+? mobile))/i';

        if (preg_match_all($mobile_regex, $user_agent)) {
            return 'mobile';
        } else {

            if (preg_match_all($tablet_regex, $user_agent)) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }
    }

    public function extraKeyword()
    {
        $keyArr = [
            __('Branch name'),
            __('Award name'),
            __('Occasion name'),
            __('Company policy name'),
            __('Ticket priority'),
            __('Event name'),
            __('Purpose of visit'),
            __('Place of visit'),
            __('Contract number'),
            __('Contract company name'),
        ];
    }

    public static function AnnualLeaveCycle()
    {
        $start_date = '' . date('Y') . '-01-01';
        $end_date = '' . date('Y') . '-12-31';
        $start_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
        $end_date = date('Y-m-d', strtotime($end_date . ' +1 day'));

        $date['start_date'] = $start_date;
        $date['end_date']   = $end_date;

        return $date;
    }

    public static function flagOfCountry()
    {
        $arr = [
            'ar' => '🇦🇪 ar',
            'da' => '🇩🇰 da',
            'de' => '🇩🇪 de',
            'es' => '🇪🇸 es',
            'fr' => '🇫🇷 fr',
            'it' =>  '🇮🇹 it',
            'ja' => '🇯🇵 ja',
            'nl' => '🇳🇱 nl',
            'pl' => '🇵🇱 pl',
            'ru' => '🇷🇺 ru',
            'pt' => '🇵🇹 pt',
            'en' => '🇮🇳 en',
            'tr' => '🇹🇷 tr',
            'pt-br' => '🇵🇹 pt-br',
        ];
        return $arr;
    }

    public static function languagecreate()
    {
        $languages = Utility::langList();
        foreach ($languages as $key => $lang) {
            $languageExist = Languages::where('code', $key)->first();
            if (empty($languageExist)) {
                $language = new Languages();
                $language->code = $key;
                $language->fullName = $lang;
                $language->save();
            }
        }
    }

    public static function langSetting()
    {
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', 1)->get();
        if (count($data) == 0) {
            $data = DB::table('settings')->where('created_by', '=', 1)->get();
        }
        $settings = [];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function langList()
    {
        $languages = [
            "ar" => "Arabic",
            "zh" => "Chinese",
            "da" => "Danish",
            "de" => "German",
            "en" => "English",
            "es" => "Spanish",
            "fr" => "French",
            "he" => "Hebrew",
            "it" => "Italian",
            "ja" => "Japanese",
            "nl" => "Dutch",
            "pl" => "Polish",
            "pt" => "Portuguese",
            "ru" => "Russian",
            "tr" => "Turkish",
            "pt-br" => "Portuguese(Brazil)"
        ];
        return $languages;
    }

    public static function get_messenger_packages_migration()
    {
        $totalMigration = 0;
        $messengerPath  = glob(base_path() . '/vendor/munafio/chatify/database/migrations' . DIRECTORY_SEPARATOR . '*.php');
        if (!empty($messengerPath)) {
            $messengerMigration = str_replace('.php', '', $messengerPath);
            $totalMigration     = count($messengerMigration);
        }

        return $totalMigration;
    }
}
