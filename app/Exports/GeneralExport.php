<?php

namespace App\Exports;

use App\Models\Allowance;
use App\Models\Employee;
use App\Models\OtherPayment;
use App\Models\SaturationDeduction;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class GeneralExport implements FromView
{
    public function view(): View
    {
        $employees = Employee::all();
        $whole_employee = [];
        foreach ($employees as $employee) {
            $allowance = Allowance::selectRaw('allowance_option, allowance_options.name as name, amount')->join('allowance_options', 'allowance_option', '=', 'allowance_options.id')->where('employee_id', $employee->id)->groupby('allowances.allowance_option')->get();

            $allowance_total = Allowance::selectRaw('sum(allowances.amount) as total')->join('allowance_options', 'allowance_option', '=', 'allowance_options.id')->where('employee_id', $employee->id)->groupby('allowances.employee_id')->get();

            $deduction = SaturationDeduction::selectRaw('*, deduction_option, deduction_options.name as name, amount')->join('deduction_options', 'deduction_option', '=', 'deduction_options.id')->where('employee_id', $employee->id)->groupby('saturation_deductions.deduction_option')->get();

            $deduction_total = SaturationDeduction::selectRaw('sum(saturation_deductions.amount) as total')->join('deduction_options', 'deduction_option', '=', 'deduction_options.id')->where('employee_id', $employee->id)->groupby('saturation_deductions.employee_id')->get();

            $otherPayment = OtherPayment::selectRaw('other_payments.title, other_payments.amount')->join('employees', 'other_payments.employee_id', '=', 'employees.id')->where('other_payments.employee_id', $employee->id)->get();

            /* header('Content-Type: application/json');
            die(json_encode($otherPayment)); */

            $abatt_key = null;
            foreach ($deduction as $key => $value) {
                if ($value->name == 'Abatt') $abatt_key = $key;
            }

            $cnr_key = null;
            foreach ($deduction as $key => $value) {
                if ($value->name == 'CNR') $cnr_key = $key;
            }

            if ($abatt_key !== null) {
                $deduction[$abatt_key]->amount = (int) bcmul((($employee->salary + $allowance_total[0]->total) / 100), 5);
                unset($deduction[$abatt_key]);
            } else {
                $abatt_registree                   = new SaturationDeduction();
                $abatt_registree->employee_id      = $employee->id;
                $abatt_registree->deduction_option = 12;
                $abatt_registree->title            = '';
                $abatt_registree->type            = 'fixe';
                $abatt_registree->amount           = (int) bcmul((($employee->salary + $allowance_total[0]->total) / 100), 5);
                $abatt_registree->created_by       = \Auth::user()->creatorId();
                $abatt_registree->save();
            }

            if ($cnr_key !== null) {
                $deduction[$cnr_key]->amount = (int) bcmul(($employee->salary / 100), 7);
                unset($deduction[$cnr_key]);
            } else {
                $cnr_registree                   = new SaturationDeduction();
                $cnr_registree->employee_id      = $employee->id;
                $cnr_registree->deduction_option = 12;
                $cnr_registree->title            = '';
                $cnr_registree->type            = 'fixe';
                $cnr_registree->amount           = (int) bcmul(($employee->salary / 100), 7);
                $cnr_registree->created_by       = \Auth::user()->creatorId();
                $cnr_registree->save();
            }

            array_push($whole_employee, ['name' => $employee->name, 'grade' => $employee->grade, 'indice' => $employee->indice, 'salary' => $employee->salary, 'cnr' => (int) bcmul(($employee->salary / 100), 7),'abatt' => (int) bcmul((($employee->salary + $allowance_total[0]->total) / 100), 5), 'allowance_total' => $allowance_total[0]->total, 'deduction_total' => $deduction_total[0]->total, bcmul(($employee->salary / 100), 7), 'retmedical' => (int) bcmul((($employee->salary + $allowance_total[0]->total - ((int) bcmul((($employee->salary + $allowance_total[0]->total) / 100), 5))) / 100), 2), 'allowance' => $allowance, 'deduction' => $deduction, 'otherPayment' => $otherPayment, 'net' => Employee::find($employee->id)->get_net_salary()]);
        }

        /* header('Content-Type: application/json');
        die(json_encode($whole_employee)); */

        return view('payslip.export', [
            'employees' => $whole_employee
        ]);
    }
}
