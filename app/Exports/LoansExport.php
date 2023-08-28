<?php

namespace App\Exports;

use App\Http\Controllers\ReportController;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LoansExport implements FromView
{
    public function view(): View
    {
        return view('loan.export', [
            'loans' => ReportController::exportLoans()
        ]);
    }
}
