<?php
/**
 * Created by PhpStorm.
 * User: Hypercube
 * Date: 23/08/2023
 * Time: 14:48
 */

namespace App\Exports;
use App\Http\Controllers\ReportController;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DeductionExport implements FromView
{

    public function view(): View
    {
        return view('saturationdeduction.export', [
            'all_deductions' => ReportController::exportDeductions()
        ]);
    }
}