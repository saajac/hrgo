<?php

namespace App\Exports;

use App\Models\Allowance;
use App\Models\Employee;
use App\Models\OtherPayment;
use App\Models\PaySlip;
use App\Models\SaturationDeduction;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class GeneralExport implements FromView
{
    public function view(): View
    {
        $employees = Employee::all();
        /* foreach ($employees as $employee) {
            $allowance                   = new SaturationDeduction();
            $allowance->employee_id      = $employee->id;
            $allowance->deduction_option = 14;
            $allowance->title            = 'CNR';
            $allowance->amount           = (int) bcmul(($employee->salary / 100), 7);
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();
        } */
        $whole_employee = [];
        foreach ($employees as $employee) {

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

            /* $whole_deduction    = SaturationDeduction::where('employee_id', '=', $employee->id)->where('deduction_option', '=', 2)->get();
            $whole_deduction[0]->amount = (int) bcmul((($employee->salary + $total_allowance) / 100), $whole_deduction[0]->amount);
            $whole_deduction[0]->type = 'fixe'; */

            /* header('Content-Type: application/json');
            die(json_encode($whole_deduction)); */

            $paySlip = PaySlip::selectRaw('*')->where('employee_id', $employee->id)->get();
            if (count($paySlip) >= 1) {
                $allowance = json_decode($paySlip[0]->allowance);
                $deduction = json_decode($paySlip[0]->saturation_deduction);
                $otherPayment = json_decode($paySlip[0]->other_payment);

                array_push($whole_employee, ['name' => $employee->name, 'grade' => $employee->grade, 'indice' => $employee->indice, 'salary' => $employee->salary, 'allowance' => $allowance, 'deduction' => $deduction, 'otherPayment' => $otherPayment, 'net' => Employee::find($employee->id)->get_net_salary(), 'retmedical' => (int) bcmul((($employee->salary + $total_allowance - ((int) bcmul((($employee->salary + $total_allowance) / 100), 5))) / 100), 2), 'allowance_total' => $total_allowance]);
            } else {
                header('Content-Type: application/json');
                die(json_encode([0 => 'Done']));
            }

            /* $abatt_key = null;
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
            } */

            /* array_push($whole_employee, ['name' => $employee->name, 'grade' => $employee->grade, 'indice' => $employee->indice, 'salary' => $employee->salary, 'cnr' => (int) bcmul(($employee->salary / 100), 7),'abatt' => (int) bcmul((($employee->salary + $allowance_total[0]->total) / 100), 5), 'allowance_total' => $allowance_total[0]->total, 'deduction_total' => $deduction_total[0]->total, bcmul(($employee->salary / 100), 7), 'retmedical' => (int) bcmul((($employee->salary + $allowance_total[0]->total - ((int) bcmul((($employee->salary + $allowance_total[0]->total) / 100), 5))) / 100), 2), 'allowance' => $allowance, 'deduction' => $deduction, 'otherPayment' => $otherPayment, 'net' => Employee::find($employee->id)->get_net_salary()]); */
        }

        /* header('Content-Type: application/json');
        die(json_encode($whole_employee)); */

        return view('payslip.export', [
            'employees' => $whole_employee
        ]);
    }
}
