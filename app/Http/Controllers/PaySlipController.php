<?php

namespace App\Http\Controllers;

use App\Exports\GeneralExport;
use App\Exports\PayslipExport;
use App\Exports\PaySlipExportTest;
use App\Models\Allowance;
use App\Models\Commission;
use App\Models\Employee;
use App\Models\Loan;
use App\Mail\InvoiceSend;
use App\Mail\PayslipSend;
use App\Models\AllowanceOption;
use App\Models\DeductionOption;
use App\Models\LoanOption;
use App\Models\OtherPayment;
use App\Models\Overtime;
use App\Models\PaySlip;
use App\Models\SaturationDeduction;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class PaySlipController extends Controller
{

    public function index()
    {
        if (\Auth::user()->can('Manage Pay Slip') || \Auth::user()->type == 'employee') {
            $employees = Employee::where(
                [
                    'created_by' => \Auth::user()->creatorId(),
                ]
            )->first();

            $month = [
                '01' => 'JAN',
                '02' => 'FEB',
                '03' => 'MAR',
                '04' => 'APR',
                '05' => 'MAY',
                '06' => 'JUN',
                '07' => 'JUL',
                '08' => 'AUG',
                '09' => 'SEP',
                '10' => 'OCT',
                '11' => 'NOV',
                '12' => 'DEC',
            ];

            $year = [
                // '2020' => '2020',
                '2021' => '2021',
                '2022' => '2022',
                '2023' => '2023',
                '2024' => '2024',
                '2025' => '2025',
                '2026' => '2026',
                '2027' => '2027',
                '2028' => '2028',
                '2029' => '2029',
                '2030' => '2030',
            ];

            return view('payslip.index', compact('employees', 'month', 'year'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'month' => 'required',
                'year' => 'required',

            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $month = $request->month;
        $year  = $request->year;





        $formate_month_year = $year . '-' . $month;
        $validatePaysilp    = PaySlip::where('salary_month', '=', $formate_month_year)->where('created_by', \Auth::user()->creatorId())->pluck('employee_id');
        $payslip_employee   = Employee::where('created_by', \Auth::user()->creatorId())->where('company_doj', '<=', date($year . '-' . $month . '-t'))->count();

        if ($payslip_employee > count($validatePaysilp)) {
            $employees = Employee::where('created_by', \Auth::user()->creatorId())->where('company_doj', '<=', date($year . '-' . $month . '-t'))->whereNotIn('employee_id', $validatePaysilp)->get();

            $employeesSalary = Employee::where('created_by', \Auth::user()->creatorId())->where('salary', '<=', 0)->first();

            if (!empty($employeesSalary)) {
                return redirect()->route('payslip.index')->with('error', __('Please set employee salary.'));
            }

            foreach ($employees as $employee) {

                $check = Payslip::where('employee_id', $employee->id)->where('salary_month', $formate_month_year)->first();

                if (!$check && $check == null) {

                    $allowances      = Allowance::where('employee_id', '=', $employee->id)->get();
                    $total_allowance = 0;
                    foreach ($allowances as $allowance) {
                        if ($allowance->type == 'percentage') {
                            $employee          = Employee::find($allowance->employee_id);
                            $total_allowance  = $allowance->amount * $employee->salary / 100  + $total_allowance;
                        } else {
                            $total_allowance = $allowance->amount + $total_allowance;
                        }
                    }

                    $abatt      = SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 2)->where('type', '=', 'percentage')->get();
                    if (count($abatt) > 0) {
                        $abatt = $abatt[0]->amount;
                        if (($employee->salary + $total_allowance) > 80000) SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 2)->update([
                            'amount' => (int) bcmul((($employee->salary + $total_allowance) / 100), $abatt),
                            'type' => 'fixed'
                        ]);
                        else {
                            SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 2)->update([
                                'amount' => 0,
                                'type' => 'fixed'
                            ]);
                        }
                    }

                    $cnr      = SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 1)->where('type', '=', 'percentage')->get();
                    if (count($cnr) > 0) {
                        $cnr = $cnr[0]->amount;
                        SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 1)->update([
                            'amount' => (int) bcmul(($employee->salary / 100), $cnr),
                            'type' => 'fixed'
                        ]);
                    }

                    $retmedi      = SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 5)->where('type', '=', 'percentage')->get();
                    $abatt      = SaturationDeduction::where('employee_id', '=', $employee->id)->get();
                    die(json_encode($abatt));
                    $abatt = $abatt[0]->amount;
                    if (count($retmedi) > 0) {
                        $retmedi = $retmedi[0]->amount;
                        $abatt      = SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 2)->get();
                        $abatt = $abatt[0]->amount;
                        SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 5)->update([
                            'amount' => (int) bcmul((($employee->salary + $total_allowance - $abatt) / 100), 2),
                            'type' => 'fixed'
                        ]);
                    }

                    $payslipEmployee                       = new PaySlip();
                    $payslipEmployee->employee_id          = $employee->id;
                    $payslipEmployee->net_payble           = $employee->get_net_salary();
                    $payslipEmployee->salary_month         = $formate_month_year;
                    $payslipEmployee->status               = 0;
                    $payslipEmployee->basic_salary         = !empty($employee->salary) ? $employee->salary : 0;
                    $payslipEmployee->allowance            = Employee::allowance($employee->id);
                    $payslipEmployee->commission           = Employee::commission($employee->id);
                    $payslipEmployee->loan                 = Employee::loan($employee->id);
                    $payslipEmployee->saturation_deduction = Employee::saturation_deduction($employee->id);
                    $payslipEmployee->other_payment        = Employee::other_payment($employee->id);
                    $payslipEmployee->overtime             = Employee::overtime($employee->id);
                    $payslipEmployee->created_by           = \Auth::user()->creatorId();

                    $payslipEmployee->save();
                    // }

                    // slack
                    $setting = Utility::settings(\Auth::user()->creatorId());
                    $month = date('M Y', strtotime($payslipEmployee->salary_month . ' ' . $payslipEmployee->time));
                    if (isset($setting['monthly_payslip_notification']) && $setting['monthly_payslip_notification'] == 1) {
                        // $msg = ("payslip generated of") . ' ' . $month . '.';

                        $uArr = [
                            'year' => $formate_month_year,
                        ];
                        Utility::send_slack_msg('new_monthly_payslip', $uArr);
                    }

                    // telegram
                    $setting = Utility::settings(\Auth::user()->creatorId());
                    $month = date('M Y', strtotime($payslipEmployee->salary_month . ' ' . $payslipEmployee->time));
                    if (isset($setting['telegram_monthly_payslip_notification']) && $setting['telegram_monthly_payslip_notification'] == 1) {
                        // $msg = ("payslip generated of") . ' ' . $month . '.';

                        $uArr = [
                            'year' => $formate_month_year,
                        ];

                        Utility::send_telegram_msg('new_monthly_payslip', $uArr);
                    }
                    // twilio
                    $setting  = Utility::settings(\Auth::user()->creatorId());
                    $emp = Employee::where('id', $payslipEmployee->employee_id = \Auth::user()->id)->first();
                    if (isset($setting['twilio_monthly_payslip_notification']) && $setting['twilio_monthly_payslip_notification'] == 1) {
                        $employeess = Employee::where($request->employee_id)->get();
                        foreach ($employeess as $key => $employee) {
                            // $msg = ("payslip generated of") . ' ' . $month . '.';

                            $uArr = [
                                'year' => $formate_month_year,
                            ];
                            Utility::send_twilio_msg($emp->phone, 'new_monthly_payslip', $uArr);
                        }
                    }

                    //webhook
                    $module = 'New Monthly Payslip';
                    $webhook =  Utility::webhookSetting($module);
                    if ($webhook) {
                        $parameter = json_encode($payslipEmployee);
                        // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                        $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                        if ($status == true) {
                            return redirect()->back()->with('success', __('Payslip successfully created.'));
                        } else {
                            return redirect()->back()->with('error', __('Webhook call failed.'));
                        }
                    }
                }
            }
            return redirect()->route('payslip.index')->with('success', __('Payslip successfully created.'));
        } else {
            return redirect()->route('payslip.index')->with('error', __('Payslip Already created.'));
        }
    }

    public function initiation()
    {
        $declared_allowance = AllowanceOption::all();
        $declared_deduction = DeductionOption::all();
        $declared_loans = LoanOption::all();

        $employees = Employee::all();

        \DB::table('allowances')->truncate();
        \DB::table('saturation_deductions')->truncate();
        \DB::table('loans')->truncate();

        foreach ($employees as $employee) {
            foreach ($declared_allowance as $allowance) {
                switch ($allowance->id) {
                    case '2':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->amount           = '5354';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '3':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->amount           = '5333';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '4':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->amount           = '8041';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '5':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->amount           = '0';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '6':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->amount           = '0';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;
                }
            }

            foreach ($declared_deduction as $deduction) {
                switch ($deduction->id) {
                    case '1':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '7';
                        $new_deduction->type           = 'percentage';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '2':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '5';
                        $new_deduction->type           = 'percentage';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '3':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '4':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '400';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '5':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '2';
                        $new_deduction->type           = 'percentage';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '6':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '7':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '8':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '9':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '1000';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '10':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '11':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '12':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '14':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;
                }
            }
        }

        header('Content-Type: application/json');
        die(json_encode([Allowance::all(), SaturationDeduction::all()]));
    }

    public function exportPaySlip(Request $request)
    {
        /*
        foreach ($employees as $employee) {
            $allowance                   = new OtherPayment();
            $allowance->employee_id      = $employee->id;
            $allowance->title            = 'All, eau';
            $allowance->amount           = '620';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }

        foreach ($employees as $employee) {
            $allowance                   = new OtherPayment();
            $allowance->employee_id      = $employee->id;
            $allowance->title            = 'Press Fam';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }

        foreach ($employees as $employee) {
            $allowance                   = new OtherPayment();
            $allowance->employee_id      = $employee->id;
            $allowance->title            = 'Pm Forfaitaire';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }

        foreach ($employees as $employee) {
            $allowance                   = new OtherPayment();
            $allowance->employee_id      = $employee->id;
            $allowance->title            = 'PFranc';
            $allowance->amount           = '27000';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } */
        /* 
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 1;
            $allowance->title            = 'CNR';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 2;
            $allowance->title            = 'Abatt';
            $allowance->type            = 'fixe';
            $allowance->amount           = '';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 4;
            $allowance->title            = 'Ret Waqf';
            $allowance->type            = 'fixe';
            $allowance->amount           = '400';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 5;
            $allowance->title            = 'Ret Medical';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 6;
            $allowance->title            = 'Sai Arret';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 7;
            $allowance->title            = 'FONT HABITAT';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        }
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 8;
            $allowance->title            = 'Ret logem';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } 
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 9;
            $allowance->title            = 'RET COLLECT';
            $allowance->type            = 'fixe';
            $allowance->amount           = '1000';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } 
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 10;
            $allowance->title            = 'Ret Sub';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } 
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 11;
            $allowance->title            = 'Ret foyer';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } 
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 12;
            $allowance->title            = 'RET POPOTE';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } 
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 14;
            $allowance->title            = 'RET A,S';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } 
        foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 16;
            $allowance->title            = 'Ret Algamil';
            $allowance->type            = 'fixe';
            $allowance->amount           = '0';
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } 
*/

        $_SESSION['salary_month'] = isset($request->month) ? $request->month : date('Y-m');

        return Excel::download(new GeneralExport, 'generalExport.xlsx');
    }

    public function destroy($id)
    {
        $payslip = PaySlip::find($id);
        $payslip->delete();

        return true;
    }

    public function showemployee($paySlip)
    {

        $payslip = PaySlip::find($paySlip);

        return view('payslip.show', compact('payslip'));
    }
    public function search_json(Request $request)
    {

        $formate_month_year = $request->datePicker;
        $validatePaysilp    = PaySlip::where('salary_month', '=', $formate_month_year)->where('created_by', \Auth::user()->creatorId())->get()->toarray();

        $data = [];
        if (empty($validatePaysilp)) {
            $data = [];
            return;
        } else {
            $paylip_employee = PaySlip::select(
                [
                    'employees.id',
                    'employees.employee_id',
                    'employees.name',
                    'payslip_types.name as payroll_type',
                    'pay_slips.basic_salary',
                    'pay_slips.net_payble',
                    'pay_slips.id as pay_slip_id',
                    'pay_slips.status',
                    'employees.user_id',
                ]
            )->leftjoin(
                'employees',
                function ($join) use ($formate_month_year) {
                    $join->on('employees.id', '=', 'pay_slips.employee_id');
                    $join->on('pay_slips.salary_month', '=', \DB::raw("'" . $formate_month_year . "'"));
                    $join->leftjoin('payslip_types', 'payslip_types.id', '=', 'employees.salary_type');
                }
            )->where('employees.created_by', \Auth::user()->creatorId())->get();


            foreach ($paylip_employee as $employee) {

                if (Auth::user()->type == 'employee') {
                    if (Auth::user()->id == $employee->user_id) {
                        $tmp   = [];
                        $tmp[] = $employee->id;
                        $tmp[] = $employee->name;
                        $tmp[] = $employee->payroll_type;
                        $tmp[] = $employee->pay_slip_id;
                        $tmp[] = !empty($employee->basic_salary) ? \Auth::user()->priceFormat($employee->basic_salary) : '-';
                        $tmp[] = !empty($employee->net_payble) ? \Auth::user()->priceFormat($employee->net_payble) : '-';
                        if ($employee->status == 1) {
                            $tmp[] = 'paid';
                        } else {
                            $tmp[] = 'unpaid';
                        }
                        $tmp[]  = !empty($employee->pay_slip_id) ? $employee->pay_slip_id : 0;
                        $tmp['url']  = route('employee.show', Crypt::encrypt($employee->id));
                        $data[] = $tmp;
                    }
                } else {

                    $tmp   = [];
                    $tmp[] = $employee->id;
                    $tmp[] = \Auth::user()->employeeIdFormat($employee->employee_id);
                    $tmp[] = $employee->name;
                    $tmp[] = $employee->payroll_type;
                    $tmp[] = !empty($employee->basic_salary) ? \Auth::user()->priceFormat($employee->basic_salary) : '-';
                    $tmp[] = !empty($employee->net_payble) ? \Auth::user()->priceFormat($employee->net_payble) : '-';
                    if ($employee->status == 1) {
                        $tmp[] = 'Paid';
                    } else {
                        $tmp[] = 'UnPaid';
                    }
                    $tmp[]  = !empty($employee->pay_slip_id) ? $employee->pay_slip_id : 0;
                    $tmp['url']  = route('employee.show', Crypt::encrypt($employee->id));
                    $data[] = $tmp;
                }
            }
            return $data;
        }
    }

    public function paysalary($id, $date)
    {
        $employeePayslip = PaySlip::where('employee_id', '=', $id)->where('created_by', \Auth::user()->creatorId())->where('salary_month', '=', $date)->first();
        if (!empty($employeePayslip)) {
            $employeePayslip->status = 1;
            $employeePayslip->save();

            return redirect()->route('payslip.index')->with('success', __('Payslip Payment successfully.'));
        } else {
            return redirect()->route('payslip.index')->with('error', __('Payslip Payment failed.'));
        }
    }

    public function bulk_pay_create($date)
    {
        $Employees       = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->get();
        $unpaidEmployees = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->where('status', '=', 0)->get();

        return view('payslip.bulkcreate', compact('Employees', 'unpaidEmployees', 'date'));
    }

    public function bulkpayment(Request $request, $date)
    {
        $unpaidEmployees = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->where('status', '=', 0)->get();

        foreach ($unpaidEmployees as $employee) {
            $employee->status = 1;
            $employee->save();
        }

        return redirect()->route('payslip.index')->with('success', __('Payslip Bulk Payment successfully.'));
    }

    public function employeepayslip()
    {
        $employees = Employee::where(
            [
                'user_id' => \Auth::user()->id,
            ]
        )->first();

        $payslip = PaySlip::where('employee_id', '=', $employees->id)->get();

        return view('payslip.employeepayslip', compact('payslip'));
    }

    public function pdf($id, $month)
    {

        $payslip  = PaySlip::where('employee_id', $id)->where('salary_month', $month)->where('created_by', \Auth::user()->creatorId())->first();
        $employee = Employee::find($payslip->employee_id);

        $payslipDetail = Utility::employeePayslipDetail($id, $month);

        return view('payslip.pdf', compact('payslip', 'employee', 'payslipDetail'));
    }

    public function send($id, $month)
    {
        $payslip  = PaySlip::where('employee_id', $id)->where('salary_month', $month)->where('created_by', \Auth::user()->creatorId())->first();
        $employee = Employee::find($payslip->employee_id);
        $payslip->name  = $employee->name;
        $payslip->email = $employee->email;

        $payslipId    = Crypt::encrypt($payslip->id);
        $payslip->url = route('payslip.payslipPdf', $payslipId);

        $setings = Utility::settings();
        if ($setings['new_payroll'] == 1) {
            $uArr = [
                'payslip_email' => $payslip->email,
                'name'  => $payslip->name,
                'url' => $payslip->url,
                'salary_month' => $payslip->salary_month,
            ];

            $resp = Utility::sendEmailTemplate('new_payroll', [$payslip->email], $uArr);
            return redirect()->back()->with('success', __('Payslip successfully sent.')  . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }

        return redirect()->back()->with('success', __('Payslip successfully sent.'));
    }

    public function payslipPdf($id)
    {
        $payslipId = Crypt::decrypt($id);

        $payslip  = PaySlip::where('id', $payslipId)->where('created_by', \Auth::user()->creatorId())->first();
        $month = $payslip->salary_month;
        $employee = Employee::find($payslip->employee_id);

        $payslipDetail = Utility::employeePayslipDetail($payslip->employee_id, $month);

        return view('payslip.payslipPdf', compact('payslip', 'employee', 'payslipDetail'));
    }

    public function editEmployee($paySlip)
    {
        $payslip = PaySlip::find($paySlip);

        return view('payslip.salaryEdit', compact('payslip'));
    }

    public function updateEmployee(Request $request, $id)
    {


        if (isset($request->allowance) && !empty($request->allowance)) {
            $allowances   = $request->allowance;
            $allowanceIds = $request->allowance_id;
            foreach ($allowances as $k => $allownace) {
                $allowanceData         = Allowance::find($allowanceIds[$k]);
                $allowanceData->amount = $allownace;
                $allowanceData->save();
            }
        }


        if (isset($request->commission) && !empty($request->commission)) {
            $commissions   = $request->commission;
            $commissionIds = $request->commission_id;
            foreach ($commissions as $k => $commission) {
                $commissionData         = Commission::find($commissionIds[$k]);
                $commissionData->amount = $commission;
                $commissionData->save();
            }
        }

        if (isset($request->loan) && !empty($request->loan)) {
            $loans   = $request->loan;
            $loanIds = $request->loan_id;
            foreach ($loans as $k => $loan) {
                $loanData         = Loan::find($loanIds[$k]);
                $loanData->amount = $loan;
                $loanData->save();
            }
        }


        if (isset($request->saturation_deductions) && !empty($request->saturation_deductions)) {
            $saturation_deductionss   = $request->saturation_deductions;
            $saturation_deductionsIds = $request->saturation_deductions_id;
            foreach ($saturation_deductionss as $k => $saturation_deductions) {

                $saturation_deductionsData         = SaturationDeduction::find($saturation_deductionsIds[$k]);
                $saturation_deductionsData->amount = $saturation_deductions;
                $saturation_deductionsData->save();
            }
        }


        if (isset($request->other_payment) && !empty($request->other_payment)) {
            $other_payments   = $request->other_payment;
            $other_paymentIds = $request->other_payment_id;
            foreach ($other_payments as $k => $other_payment) {
                $other_paymentData         = OtherPayment::find($other_paymentIds[$k]);
                $other_paymentData->amount = $other_payment;
                $other_paymentData->save();
            }
        }


        if (isset($request->rate) && !empty($request->rate)) {
            $rates   = $request->rate;
            $rateIds = $request->rate_id;
            $hourses = $request->hours;

            foreach ($rates as $k => $rate) {
                $overtime        = Overtime::find($rateIds[$k]);
                $overtime->rate  = $rate;
                $overtime->hours = $hourses[$k];
                $overtime->save();
            }
        }


        $payslipEmployee                       = PaySlip::find($request->payslip_id);
        $payslipEmployee->allowance            = Employee::allowance($payslipEmployee->employee_id);
        $payslipEmployee->commission           = Employee::commission($payslipEmployee->employee_id);
        $payslipEmployee->loan                 = Employee::loan($payslipEmployee->employee_id);
        $payslipEmployee->saturation_deduction = Employee::saturation_deduction($payslipEmployee->employee_id);
        $payslipEmployee->other_payment        = Employee::other_payment($payslipEmployee->employee_id);
        $payslipEmployee->overtime             = Employee::overtime($payslipEmployee->employee_id);
        $payslipEmployee->net_payble           = Employee::find($payslipEmployee->employee_id)->get_net_salary();
        $payslipEmployee->save();

        return redirect()->route('payslip.index')->with('success', __('Employee payroll successfully updated.'));
    }

    public function PayslipExport(Request $request)
    {
        $name = 'payslip_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new PayslipExport($request), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }
}
/*
namespace App\Http\Controllers;

use App\Exports\PayslipExport;
use App\Models\Allowance;
use App\Models\Commission;
use App\Models\Employee;
use App\Models\Loan;
use App\Mail\InvoiceSend;
use App\Mail\PayslipSend;
use App\Models\OtherPayment;
use App\Models\Overtime;
use App\Models\PaySlip;
use App\Models\SaturationDeduction;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class PaySlipController extends Controller
{

    public function index()
    {
        if (\Auth::user()->can('Manage Pay Slip') || \Auth::user()->type == 'employee') {
            $employees = Employee::where(
                [
                    'created_by' => \Auth::user()->creatorId(),
                ]
            )->first();

            $month = [
                '01' => 'JAN',
                '02' => 'FEB',
                '03' => 'MAR',
                '04' => 'APR',
                '05' => 'MAY',
                '06' => 'JUN',
                '07' => 'JUL',
                '08' => 'AUG',
                '09' => 'SEP',
                '10' => 'OCT',
                '11' => 'NOV',
                '12' => 'DEC',
            ];

            $year = [
                // '2020' => '2020',
                '2021' => '2021',
                '2022' => '2022',
                '2023' => '2023',
                '2024' => '2024',
                '2025' => '2025',
                '2026' => '2026',
                '2027' => '2027',
                '2028' => '2028',
                '2029' => '2029',
                '2030' => '2030',
            ];

            return view('payslip.index', compact('employees', 'month', 'year'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'month' => 'required',
                'year' => 'required',

            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $month = $request->month;
        $year  = $request->year;


        $formate_month_year = $year . '-' . $month;
        $validatePaysilp    = PaySlip::where('salary_month', '=', $formate_month_year)->where('created_by', \Auth::user()->creatorId())->pluck('employee_id');
        $payslip_employee   = Employee::where('created_by', \Auth::user()->creatorId())->where('company_doj', '<=', date($year . '-' . $month . '-t'))->count();

        if ($payslip_employee > count($validatePaysilp)) {
            $employees = Employee::where('created_by', \Auth::user()->creatorId())->where('company_doj', '<=', date($year . '-' . $month . '-t'))->whereNotIn('employee_id', $validatePaysilp)->get();

            $employeesSalary = Employee::where('created_by', \Auth::user()->creatorId())->where('salary', '<=', 0)->first();

            if (!empty($employeesSalary)) {
                return redirect()->route('payslip.index')->with('error', __('Please set employee salary.'));
            }

            foreach ($employees as $employee) {

                $check = Payslip::where('employee_id', $employee->id)->where('salary_month', $formate_month_year)->first();
                if (!$check && $check == null) {
                    $payslipEmployee                       = new PaySlip();
                    $payslipEmployee->employee_id          = $employee->id;
                    $payslipEmployee->net_payble           = $employee->get_net_salary();
                    $payslipEmployee->salary_month         = $formate_month_year;
                    $payslipEmployee->status               = 0;
                    $payslipEmployee->basic_salary         = !empty($employee->salary) ? $employee->salary : 0;
                    $payslipEmployee->allowance            = Employee::allowance($employee->id);
                    $payslipEmployee->commission           = Employee::commission($employee->id);
                    $payslipEmployee->loan                 = Employee::loan($employee->id);
                    $payslipEmployee->saturation_deduction = Employee::saturation_deduction($employee->id);
                    $payslipEmployee->other_payment        = Employee::other_payment($employee->id);
                    $payslipEmployee->overtime             = Employee::overtime($employee->id);
                    $payslipEmployee->created_by           = \Auth::user()->creatorId();

                    $payslipEmployee->save();
                    // }

                    // slack 
                    $setting = Utility::settings(\Auth::user()->creatorId());
                    $month = date('M Y', strtotime($payslipEmployee->salary_month . ' ' . $payslipEmployee->time));
                    if (isset($setting['monthly_payslip_notification']) && $setting['monthly_payslip_notification'] == 1) {
                        // $msg = ("payslip generated of") . ' ' . $month . '.';

                        $uArr = [
                            'year' => $formate_month_year,
                        ];
                        Utility::send_slack_msg('new_monthly_payslip', $uArr);
                    }

                    // telegram 
                    $setting = Utility::settings(\Auth::user()->creatorId());
                    $month = date('M Y', strtotime($payslipEmployee->salary_month . ' ' . $payslipEmployee->time));
                    if (isset($setting['telegram_monthly_payslip_notification']) && $setting['telegram_monthly_payslip_notification'] == 1) {
                        // $msg = ("payslip generated of") . ' ' . $month . '.';

                        $uArr = [
                            'year' => $formate_month_year,
                        ];

                        Utility::send_telegram_msg('new_monthly_payslip', $uArr);
                    }


                    // twilio
                    $setting  = Utility::settings(\Auth::user()->creatorId());
                    $emp = Employee::where('id', $payslipEmployee->employee_id = \Auth::user()->id)->first();
                    if (isset($setting['twilio_monthly_payslip_notification']) && $setting['twilio_monthly_payslip_notification'] == 1) {
                        $employeess = Employee::where($request->employee_id)->get();
                        foreach ($employeess as $key => $employee) {
                            // $msg = ("payslip generated of") . ' ' . $month . '.';

                            $uArr = [
                                'year' => $formate_month_year,
                            ];
                            Utility::send_twilio_msg($emp->phone, 'new_monthly_payslip', $uArr);
                        }
                    }

                    //webhook
                    $module = 'New Monthly Payslip';
                    $webhook =  Utility::webhookSetting($module);
                    if ($webhook) {
                        $parameter = json_encode($payslipEmployee);
                        // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                        $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                        if ($status == true) {
                            return redirect()->back()->with('success', __('Payslip successfully created.'));
                        } else {
                            return redirect()->back()->with('error', __('Webhook call failed.'));
                        }
                    }
                }
            }
            return redirect()->route('payslip.index')->with('success', __('Payslip successfully created.'));
        } else {
            return redirect()->route('payslip.index')->with('error', __('Payslip Already created.'));
        }
    }

    public function destroy($id)
    {
        $payslip = PaySlip::find($id);
        $payslip->delete();

        return true;
    }

    public function showemployee($paySlip)
    {

        $payslip = PaySlip::find($paySlip);

        return view('payslip.show', compact('payslip'));
    }
    public function search_json(Request $request)
    {

        $formate_month_year = $request->datePicker;
        $validatePaysilp    = PaySlip::where('salary_month', '=', $formate_month_year)->where('created_by', \Auth::user()->creatorId())->get()->toarray();

        $data = [];
        if (empty($validatePaysilp)) {
            $data = [];
            return;
        } else {
            $paylip_employee = PaySlip::select(
                [
                    'employees.id',
                    'employees.employee_id',
                    'employees.name',
                    'payslip_types.name as payroll_type',
                    'pay_slips.basic_salary',
                    'pay_slips.net_payble',
                    'pay_slips.id as pay_slip_id',
                    'pay_slips.status',
                    'employees.user_id',
                ]
            )->leftjoin(
                'employees',
                function ($join) use ($formate_month_year) {
                    $join->on('employees.id', '=', 'pay_slips.employee_id');
                    $join->on('pay_slips.salary_month', '=', \DB::raw("'" . $formate_month_year . "'"));
                    $join->leftjoin('payslip_types', 'payslip_types.id', '=', 'employees.salary_type');
                }
            )->where('employees.created_by', \Auth::user()->creatorId())->get();


            foreach ($paylip_employee as $employee) {

                if (Auth::user()->type == 'employee') {
                    if (Auth::user()->id == $employee->user_id) {
                        $tmp   = [];
                        $tmp[] = $employee->id;
                        $tmp[] = $employee->name;
                        $tmp[] = $employee->payroll_type;
                        $tmp[] = $employee->pay_slip_id;
                        $tmp[] = !empty($employee->basic_salary) ? \Auth::user()->priceFormat($employee->basic_salary) : '-';
                        $tmp[] = !empty($employee->net_payble) ? \Auth::user()->priceFormat($employee->net_payble) : '-';
                        if ($employee->status == 1) {
                            $tmp[] = 'paid';
                        } else {
                            $tmp[] = 'unpaid';
                        }
                        $tmp[]  = !empty($employee->pay_slip_id) ? $employee->pay_slip_id : 0;
                        $tmp['url']  = route('employee.show', Crypt::encrypt($employee->id));
                        $data[] = $tmp;
                    }
                } else {

                    $tmp   = [];
                    $tmp[] = $employee->id;
                    $tmp[] = \Auth::user()->employeeIdFormat($employee->employee_id);
                    $tmp[] = $employee->name;
                    $tmp[] = $employee->payroll_type;
                    $tmp[] = !empty($employee->basic_salary) ? \Auth::user()->priceFormat($employee->basic_salary) : '-';
                    $tmp[] = !empty($employee->net_payble) ? \Auth::user()->priceFormat($employee->net_payble) : '-';
                    if ($employee->status == 1) {
                        $tmp[] = 'Paid';
                    } else {
                        $tmp[] = 'UnPaid';
                    }
                    $tmp[]  = !empty($employee->pay_slip_id) ? $employee->pay_slip_id : 0;
                    $tmp['url']  = route('employee.show', Crypt::encrypt($employee->id));
                    $data[] = $tmp;
                }
            }
            return $data;
        }
    }

    public function paysalary($id, $date)
    {
        $employeePayslip = PaySlip::where('employee_id', '=', $id)->where('created_by', \Auth::user()->creatorId())->where('salary_month', '=', $date)->first();
        if (!empty($employeePayslip)) {
            $employeePayslip->status = 1;
            $employeePayslip->save();

            return redirect()->route('payslip.index')->with('success', __('Payslip Payment successfully.'));
        } else {
            return redirect()->route('payslip.index')->with('error', __('Payslip Payment failed.'));
        }
    }

    public function bulk_pay_create($date)
    {
        $Employees       = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->get();
        $unpaidEmployees = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->where('status', '=', 0)->get();

        return view('payslip.bulkcreate', compact('Employees', 'unpaidEmployees', 'date'));
    }

    public function bulkpayment(Request $request, $date)
    {
        $unpaidEmployees = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->where('status', '=', 0)->get();

        foreach ($unpaidEmployees as $employee) {
            $employee->status = 1;
            $employee->save();
        }

        return redirect()->route('payslip.index')->with('success', __('Payslip Bulk Payment successfully.'));
    }

    public function employeepayslip()
    {
        $employees = Employee::where(
            [
                'user_id' => \Auth::user()->id,
            ]
        )->first();

        $payslip = PaySlip::where('employee_id', '=', $employees->id)->get();

        return view('payslip.employeepayslip', compact('payslip'));
    }

    public function pdf($id, $month)
    {

        $payslip  = PaySlip::where('employee_id', $id)->where('salary_month', $month)->where('created_by', \Auth::user()->creatorId())->first();
        $employee = Employee::find($payslip->employee_id);

        $payslipDetail = Utility::employeePayslipDetail($id, $month);

        return view('payslip.pdf', compact('payslip', 'employee', 'payslipDetail'));
    }

    public function send($id, $month)
    {
        $payslip  = PaySlip::where('employee_id', $id)->where('salary_month', $month)->where('created_by', \Auth::user()->creatorId())->first();
        $employee = Employee::find($payslip->employee_id);
        $payslip->name  = $employee->name;
        $payslip->email = $employee->email;

        $payslipId    = Crypt::encrypt($payslip->id);
        $payslip->url = route('payslip.payslipPdf', $payslipId);

        $setings = Utility::settings();
        if ($setings['new_payroll'] == 1) {
            $uArr = [
                'payslip_email' => $payslip->email,
                'name'  => $payslip->name,
                'url' => $payslip->url,
                'salary_month' => $payslip->salary_month,
            ];

            $resp = Utility::sendEmailTemplate('new_payroll', [$payslip->email], $uArr);
            return redirect()->back()->with('success', __('Payslip successfully sent.')  . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }

        return redirect()->back()->with('success', __('Payslip successfully sent.'));
    }

    public function payslipPdf($id)
    {
        $payslipId = Crypt::decrypt($id);

        $payslip  = PaySlip::where('id', $payslipId)->where('created_by', \Auth::user()->creatorId())->first();
        $month = $payslip->salary_month;
        $employee = Employee::find($payslip->employee_id);

        $payslipDetail = Utility::employeePayslipDetail($payslip->employee_id, $month);

        return view('payslip.payslipPdf', compact('payslip', 'employee', 'payslipDetail'));
    }

    public function editEmployee($paySlip)
    {
        $payslip = PaySlip::find($paySlip);

        return view('payslip.salaryEdit', compact('payslip'));
    }

    public function updateEmployee(Request $request, $id)
    {


        if (isset($request->allowance) && !empty($request->allowance)) {
            $allowances   = $request->allowance;
            $allowanceIds = $request->allowance_id;
            foreach ($allowances as $k => $allownace) {
                $allowanceData         = Allowance::find($allowanceIds[$k]);
                $allowanceData->amount = $allownace;
                $allowanceData->save();
            }
        }


        if (isset($request->commission) && !empty($request->commission)) {
            $commissions   = $request->commission;
            $commissionIds = $request->commission_id;
            foreach ($commissions as $k => $commission) {
                $commissionData         = Commission::find($commissionIds[$k]);
                $commissionData->amount = $commission;
                $commissionData->save();
            }
        }

        if (isset($request->loan) && !empty($request->loan)) {
            $loans   = $request->loan;
            $loanIds = $request->loan_id;
            foreach ($loans as $k => $loan) {
                $loanData         = Loan::find($loanIds[$k]);
                $loanData->amount = $loan;
                $loanData->save();
            }
        }


        if (isset($request->saturation_deductions) && !empty($request->saturation_deductions)) {
            $saturation_deductionss   = $request->saturation_deductions;
            $saturation_deductionsIds = $request->saturation_deductions_id;
            foreach ($saturation_deductionss as $k => $saturation_deductions) {

                $saturation_deductionsData         = SaturationDeduction::find($saturation_deductionsIds[$k]);
                $saturation_deductionsData->amount = $saturation_deductions;
                $saturation_deductionsData->save();
            }
        }


        if (isset($request->other_payment) && !empty($request->other_payment)) {
            $other_payments   = $request->other_payment;
            $other_paymentIds = $request->other_payment_id;
            foreach ($other_payments as $k => $other_payment) {
                $other_paymentData         = OtherPayment::find($other_paymentIds[$k]);
                $other_paymentData->amount = $other_payment;
                $other_paymentData->save();
            }
        }


        if (isset($request->rate) && !empty($request->rate)) {
            $rates   = $request->rate;
            $rateIds = $request->rate_id;
            $hourses = $request->hours;

            foreach ($rates as $k => $rate) {
                $overtime        = Overtime::find($rateIds[$k]);
                $overtime->rate  = $rate;
                $overtime->hours = $hourses[$k];
                $overtime->save();
            }
        }


        $payslipEmployee                       = PaySlip::find($request->payslip_id);
        $payslipEmployee->allowance            = Employee::allowance($payslipEmployee->employee_id);
        $payslipEmployee->commission           = Employee::commission($payslipEmployee->employee_id);
        $payslipEmployee->loan                 = Employee::loan($payslipEmployee->employee_id);
        $payslipEmployee->saturation_deduction = Employee::saturation_deduction($payslipEmployee->employee_id);
        $payslipEmployee->other_payment        = Employee::other_payment($payslipEmployee->employee_id);
        $payslipEmployee->overtime             = Employee::overtime($payslipEmployee->employee_id);
        $payslipEmployee->net_payble           = Employee::find($payslipEmployee->employee_id)->get_net_salary();
        $payslipEmployee->save();

        return redirect()->route('payslip.index')->with('success', __('Employee payroll successfully updated.'));
    }

    public function PayslipExport(Request $request)
    {
        $name = 'payslip_' . date('Y-m-d i:h:s');
        $data = \Excel::download(new PayslipExport($request), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }
}*/
