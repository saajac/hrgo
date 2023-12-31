<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\PaySlip;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PayrollExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $month;
    protected $branch;
    protected $department;

    public function __construct($data)
    {
        $this->month = $data['month'];
        $this->branch = $data['branch'];
        $this->department = $data['department'];
    }

    public function collection()
    {
        // $data = PaySlip::select('pay_slips.*', 'employees.name')->leftjoin('employees', 'pay_slips.employee_id', '=', 'employees.id')->where('pay_slips.created_by', \Auth::user()->creatorId());

        $data = PaySlip::select('pay_slips.*', 'employees.name')->leftJoin('employees', 'pay_slips.employee_id', '=', 'employees.id')->where('pay_slips.created_by', \Auth::user()->creatorId())->where('employees.branch_id', $this->branch)->where('employees.department_id', $this->department);

        $month = date('Y-m');

        $data->where('salary_month', $this->month);

        $filterYear['dateYearRange'] = date('M-Y', strtotime($month));
        $filterYear['type']          = __('Monthly');

        $data = $data->get();

        foreach ($data as $k => $payslip) {
            $payslip["employee_id"] = !empty($payslip->employees) ? \Auth::user()->employeeIdFormat($payslip->employees->employee_id) : '';
            $payslip["employee_name"] = (!empty($payslip->name)) ? $payslip->name : '';
            $payslip["salary"] = \Auth::user()->priceFormat($payslip->basic_salary);
            $payslip["net_salary"] = \Auth::user()->priceFormat($payslip->net_payble);
            $payslip["month"] = $payslip->salary_month;
            $payslip["status"] = $payslip->status == 0 ? 'UnPaid' :  'Paid';
            unset($payslip->created_at, $payslip->updated_at, $payslip->allowance, $payslip->commission, $payslip->loan, $payslip->saturation_deduction, $payslip->other_payment, $payslip->overtime, $payslip->saturation_deduction, $payslip->created_by, $payslip->id, $payslip->name, $payslip->net_payble, $payslip->basic_salary, $payslip->salary_month);
        }

        return $data;
    }
    public function headings(): array
    {
        return [
            "Employee Id",
            "Status",
            "Employee Name",
            "Salary",
            "Net Salary",
            "Month",
        ];
    }
}
