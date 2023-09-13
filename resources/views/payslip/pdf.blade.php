@php
// $logo = asset(Storage::url('uploads/logo/'));
$logo = \App\Models\Utility::get_file('uploads/logo/');

$company_logo = Utility::getValByName('company_logo');
@endphp
@php
$otherPayments = $payslipDetail['earning']['otherPayment'][0]->other_payment;
$data = [];
@endphp
<script src="https://cdn.tailwindcss.com"></script>

<style>
    * {
        font-size: 15px;
    }
</style>

<div class="modal-body">
    <div class="text-md-end mb-2">
        <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ __('Download') }}" onclick="saveAsPDF()"><span class="fa fa-download"></span></a>
        @if (\Auth::user()->type == 'company' || \Auth::user()->type == 'hr')
        <a title="Mail Send" href="{{ route('payslip.send', [$employee->id, $payslip->salary_month]) }}" class="btn btn-sm btn-warning"><span class="fa fa-paper-plane"></span></a>
        @endif
    </div>
    <!-- <div class="invoice" id="printableArea">
        <div class="row">
            <div class="col-form-label">
                <div class="invoice-number">
                    <img src="{{ $logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'dark_logo.png') }}"
                        width="170px;">
                </div>


                <div class="invoice-print">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="invoice-title">
                                {{-- <h6 class="mb-3">{{ __('Payslip') }}</h6> --}}

                            </div>
                            <hr>
                            <div class="row text-sm">
                                <div class="col-md-6">
                                    <address>
                                        <strong>{{ __('Name') }} :</strong> {{ $employee->name }}<br>
                                        <strong>{{ __('Position') }} :</strong> {{ __('Employee') }}<br>
                                        <strong>{{ __('Salary Date') }} :</strong>
                                        {{ \Auth::user()->dateFormat($payslip->created_at) }}<br>
                                    </address>
                                </div>
                                <div class="col-md-6 text-end">
                                    <address>
                                        <strong>{{ \Utility::getValByName('company_name') }} </strong><br>
                                        {{ \Utility::getValByName('company_address') }} ,
                                        {{ \Utility::getValByName('company_city') }},<br>
                                        {{ \Utility::getValByName('company_state') }}-{{ \Utility::getValByName('company_zipcode') }}<br>
                                        <strong>{{ __('Salary Slip') }} :</strong> {{ $payslip->salary_month }}<br>
                                    </address>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table  table-md">
                                    <tbody>
                                        <tr class="font-weight-bold">
                                            <th>{{ __('Earning') }}</th>
                                            <th>{{ __('Title') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th class="text-right">{{ __('Amount') }}</th>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Basic Salary') }}</td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td class="text-right">
                                                {{ \Auth::user()->priceFormat($payslip->basic_salary) }}</td>
                                        </tr>

                                        @foreach ($payslipDetail['earning']['allowance'] as $allowance)
                                            @php
                                                $employess = \App\Models\Employee::find($allowance->employee_id);
                                                $allowance = json_decode($allowance->allowance);
                                            @endphp
                                            @foreach ($allowance as $all)
                                                <tr>
                                                    <td>{{ __('Allowance') }}</td>
                                                    <td>{{ $all->title }}</td>
                                                    <td>{{ ucfirst($all->type) }}</td>
                                                    @if ($all->type != 'percentage')
                                                        <td class="text-right">
                                                            {{ \Auth::user()->priceFormat($all->amount) }}</td>
                                                    @else
                                                        <td class="text-right">{{ $all->amount }}%
                                                            ({{ \Auth::user()->priceFormat(($all->amount * $payslip->basic_salary) / 100) }})
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach

                                        @foreach ($payslipDetail['earning']['commission'] as $commission)
                                            @php
                                                $employess = \App\Models\Employee::find($commission->employee_id);
                                                $commissions = json_decode($commission->commission);
                                            @endphp
                                            @foreach ($commissions as $empcom)
                                                <tr>
                                                    <td>{{ __('Commission') }}</td>
                                                    <td>{{ $empcom->title }}</td>
                                                    <td>{{ ucfirst($empcom->type) }}</td>
                                                    @if ($empcom->type != 'percentage')
                                                        <td class="text-right">
                                                            {{ \Auth::user()->priceFormat($empcom->amount) }}</td>
                                                    @else
                                                        <td class="text-right">{{ $empcom->amount }}%
                                                            ({{ \Auth::user()->priceFormat(($empcom->amount * $payslip->basic_salary) / 100) }})
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach

                                        @foreach ($payslipDetail['earning']['otherPayment'] as $otherPayment)
                                            @php
                                                $employess = \App\Models\Employee::find($otherPayment->employee_id);
                                                $otherpay = json_decode($otherPayment->other_payment);
                                            @endphp
                                            @foreach ($otherpay as $op)
                                                <tr>
                                                    <td>{{ __('Other Payment') }}</td>
                                                    <td>{{ $op->title }}</td>
                                                    <td>{{ ucfirst($op->type) }}</td>
                                                    @if ($op->type != 'percentage')
                                                        <td class="text-right">
                                                            {{ \Auth::user()->priceFormat($op->amount) }}</td>
                                                    @else
                                                        <td class="text-right">{{ $op->amount }}%
                                                            ({{ \Auth::user()->priceFormat(($op->amount * $payslip->basic_salary) / 100) }})
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach

                                        @foreach ($payslipDetail['earning']['overTime'] as $overTime)
                                            @php
                                                $arrayJson = json_decode($overTime->overtime);
                                                foreach ($arrayJson as $key => $overtime) {
                                                    foreach ($arrayJson as $key => $overtimes) {
                                                        $overtitle = $overtimes->title;
                                                        $OverTime = $overtimes->number_of_days * $overtimes->hours * $overtimes->rate;
                                                    }
                                                }
                                            @endphp
                                            @foreach ($arrayJson as $overtime)
                                                <tr>
                                                    <td>{{ __('OverTime') }}</td>
                                                    <td>{{ $overtime->title }}</td>
                                                    <td>-</td>
                                                    <td class="text-right">
                                                        {{ \Auth::user()->priceFormat($overtime->number_of_days * $overtime->hours * $overtime->rate) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-md">
                                    <tbody>
                                        <tr class="font-weight-bold">
                                            <th>{{ __('Deduction') }}</th>
                                            <th>{{ __('Title') }}</th>
                                            <th>{{ __('type') }}</th>
                                            <th class="text-right">{{ __('Amount') }}</th>
                                        </tr>

                                        @foreach ($payslipDetail['deduction']['loan'] as $loan)
                                            @php
                                                $employess = \App\Models\Employee::find($loan->employee_id);
                                                $loans = json_decode($loan->loan);
                                            @endphp
                                            @foreach ($loans as $emploanss)
                                                <tr>
                                                    <td>{{ __('Loan') }}</td>
                                                    <td>{{ $emploanss->title }}</td>
                                                    <td>{{ ucfirst($emploanss->type) }}</td>
                                                    @if ($emploanss->type != 'percentage')
                                                        <td class="text-right">
                                                            {{ \Auth::user()->priceFormat($emploanss->amount) }}</td>
                                                    @else
                                                        <td class="text-right">{{ $emploanss->amount }}%
                                                            ({{ \Auth::user()->priceFormat(($emploanss->amount * $payslip->basic_salary) / 100) }})
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach

                                        @foreach ($payslipDetail['deduction']['deduction'] as $deduction)
                                            @php
                                                $employess = \App\Models\Employee::find($deduction->employee_id);
                                                $deductions = json_decode($deduction->saturation_deduction);
                                            @endphp
                                            @foreach ($deductions as $saturationdeduc)
                                                <tr>
                                                    <td>{{ __('Saturation Deduction') }}</td>
                                                    <td>{{ $saturationdeduc->title }}</td>
                                                    <td>{{ ucfirst($saturationdeduc->type) }}</td>
                                                    @if ($saturationdeduc->type != 'percentage')
                                                        <td class="text-right">
                                                            {{ \Auth::user()->priceFormat($saturationdeduc->amount) }}
                                                        </td>
                                                    @else
                                                        <td class="text-right">{{ $saturationdeduc->amount }}%
                                                            ({{ \Auth::user()->priceFormat(($saturationdeduc->amount * $payslip->basic_salary) / 100) }})
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-4">
                                <div class="col-lg-8">

                                </div>
                                <div class="col-lg-4 text-right text-sm">
                                    <div class="invoice-detail-item pb-2">
                                        <div class="invoice-detail-name font-weight-bold">{{ __('Total Earning') }}
                                        </div>
                                        <div class="invoice-detail-value">
                                            {{ \Auth::user()->priceFormat($payslipDetail['totalEarning']) }}</div>
                                    </div>
                                    <div class="invoice-detail-item">
                                        <div class="invoice-detail-name font-weight-bold">{{ __('Total Deduction') }}
                                        </div>
                                        <div class="invoice-detail-value">
                                            {{ \Auth::user()->priceFormat($payslipDetail['totalDeduction']) }}</div>
                                    </div>
                                    <hr class="mt-2 mb-2">
                                    <div class="invoice-detail-item">
                                        <div class="invoice-detail-name font-weight-bold">{{ __('Net Salary') }}</div>
                                        <div class="invoice-detail-value invoice-detail-value-lg">
                                            {{ \Auth::user()->priceFormat($payslip->net_payble) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-md-right pb-2 text-sm">
                    <div class="float-lg-left mb-lg-0 mb-3 ">
                        <p class="mt-2">{{ __('Employee Signature') }}</p>
                    </div>
                    <p class="mt-2 "> {{ __('Paid By') }}</p>
                </div>
            </div>
        </div>

    </div> -->

    <div class="px-8 py-4" id="printableArea">
        <div class="text-left mb-4">
            <h3>REPUBIQUE DE DJIBOUTI</h3>
            <h3>MINISTERE DE L'EQUIPEMENT ET DES TRANSPORTS</h3>
            <h3>DIRECTION DE LA GARDE-CÔTES</h3>
            <h3>SERVICE SOLDE</h3>
        </div>

        <div class="text-center">
            <h1 class="text-xl font-bold my-2">
                <span class="border-4 border-black py-1 px-6">BULLETIN DE PAIE</span>
            </h1>
            <h3 class="mb-2">MOIS DE MAI 2023</h3>
        </div>
        <div class="border-t-2 border-b-2 border-black py-2 px-4">
            <table class="w-full">
                <tr>
                    <td>Matricule: &nbsp; {{ $employee->matricule }}</td>
                    <td>Status: &nbsp; Fonctionnaire</td>
                </tr>
                <tr>
                    <td>Nom: &nbsp; {{ $employee->name }}</td>
                    <td>Indice: &nbsp; {{ $employee->indice }}</td>
                </tr>
                <tr>
                    <td>Administration: &nbsp; MINISTERE DE L'EQUIPEMENT</td>
                    <td>Echelle: &nbsp; {{ $employee->echelle }}</td>
                </tr>
                <tr>
                    <td>Service: &nbsp; DIRECTION DE LA GARDE-CÔTES</td>
                    <td>Grade: &nbsp; {{ $employee->grade }}</td>
                </tr>
                <tr>
                    <td>N° Compte: &nbsp; {{ $employee->account_number }}</td>
                    <td></td>
                </tr>
            </table>
        </div>

        @php
            $allowances = [];
            $deductions = [];
            $loans = [];
            $otherPayments = [];
        @endphp

        @foreach ($payslipDetail['earning']['otherPayment'] as $otherPayment)
            @php
                $otherpay = json_decode($otherPayment->other_payment);
            @endphp
            @foreach ($otherpay as $op)
                @php array_push($otherPayments, ['title' => $op->title, 'amount' => \Auth::user()->priceFormat($op->amount)]) @endphp
            @endforeach
        @endforeach

        @foreach ($payslipDetail['deduction']['loan'] as $loan)
            @php
                $loan = json_decode($loan->loan);
            @endphp
            @foreach ($loan as $emploanss)
                @php array_push($loans, ['title' => $emploanss->title, 'amount' => \Auth::user()->priceFormat($emploanss->amount)]) @endphp
            @endforeach
        @endforeach

        @foreach ($payslipDetail['deduction']['deduction'] as $deduction)
            @php
                $deduction = json_decode($deduction->saturation_deduction);
            @endphp
            @foreach ($deduction as $saturationdeduc)
                @php array_push($deductions, ['title' => $saturationdeduc->title, 'amount' => \Auth::user()->priceFormat($saturationdeduc->amount)]) @endphp
            @endforeach
        @endforeach
        
        @foreach ($payslipDetail['earning']['allowance'] as $allowance)
            @php
                $allowance = json_decode($allowance->allowance);
            @endphp
            @foreach ($allowance as $all)
                @php array_push($allowances, ['title' => $all->title, 'amount' => \Auth::user()->priceFormat($all->amount)]) @endphp
            @endforeach
        @endforeach
        <div class="p-4 mb-4">
            <h3 class="mb-2">
                <span class="border p-1 border-2 border-black px-4 mr-8">TRAITEMENT DE LA SOLDE</span>
                <span>Solde Base: &nbsp; {{ \Auth::user()->priceFormat($employee->salary) }}</span>
            </h3>

            <table class="w-full">
                <tr class="align-top">
                    <td class="pb-2">
                        <h3 class="my-4">
                            <span class="border p-1 border-2 border-black mr-8 px-4">INDEMNITES</span>
                        </h3>
                        <table class="w-[300px]">
                            <tr>
                                <td>Ind.C.Militaire:</td>
                                <td>
                                    @foreach ( $allowances as $allowan)
                                        @if ($allowan['title'] == 'Mission Special')
                                            {{ $allowan['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>Ind.S.Police:</td>
                                <td>
                                    @foreach ( $allowances as $allowan)
                                        @if ($allowan['title'] == 'Sujet Police')
                                            {{ $allowan['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>Mensuelle_RespPart:</td>
                                <td>
                                    @foreach ( $allowances as $allowan)
                                        @if ($allowan['title'] == 'Mensuelle RespPart')
                                            {{ $allowan['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>Ind. Medecin:</td>
                                <td>
                                    @foreach ( $allowances as $allowan)
                                        @if ($allowan['title'] == 'Indemnite Medecin')
                                            {{ $allowan['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="text-right pb-2">
                        <h3 class="my-4">
                            <span class="border p-1 border-2 border-black mr-8 px-4">RETENUS</span>
                        </h3>
                        <table class="ml-auto w-[300px]">
                            <tr>
                                <td>Ret Log:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Ret logem')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach                                       
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Subv:</td>
                                <td>                                    
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Ret Sub')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach 
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Foyer:</td>
                                <td>                                    
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Ret foyer')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach                                    
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Collecte:</td>
                                <td>                                    
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'RET COLLECT')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach                                      
                                </td>
                            </tr>
                            <tr>
                                <td><mark>Ret Caisse Social:</mark></td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Ret Fond Habitat:</td>
                                <td>                                                                        
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'FONT HABITAT')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach 
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Collect Medecin:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Ret Medical')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach 
                                </td>
                            </tr>
                            <tr>
                                <td><mark>Ret Chebeleye:</mark></td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Ret Popote:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'RET POPOTE')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach 
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Waqf:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Ret Waqf')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Al-Gamil:</td>
                                <td>
                                    @foreach ( $loans as $loa)
                                        @if ($loa['title'] == 'Algamil')
                                            {{ $loa['amount'] }}
                                        @endif
                                    @endforeach 
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="align-top">
                    <td class="pt-2 pb-2">
                        <h3 class="mb-4">
                            <span class="border p-1 border-2 border-black mr-8 px-4">AUTRES AVANTAGES</span>
                        </h3>
                        <table class="w-[300px]">
                            <tr>
                                <td>Rap Autre:</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Alloc Eau:</td>
                                <td>
                                    @foreach ( $otherPayments as $otpay)
                                        @if ($otpay['title'] == 'All,eau')
                                            {{ $otpay['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>PFranc:</td>
                                <td>
                                    @foreach ( $otherPayments as $otpay)
                                        @if ($otpay['title'] == 'PFranc')
                                            {{ $otpay['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="text-right pt-2 pb-2">
                        <h3 class="mb-4">
                            <span class="border p-1 border-2 border-black mr-8 px-4">COTISATIONS</span>
                        </h3>
                        <table class="ml-auto w-[300px]">
                            <tr>
                                <td>CNR:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'CNR')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>CMR:</td>
                                <td>0</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="align-top">
                    <td class="pt-2">
                        <h3 class="mt-2 mb-4">
                            <span class="border p-1 border-2 border-black mr-8 px-4">SALAIRE NET A PAYER</span>
                        </h3>
                        <table class="w-[300px]">
                            <tr>
                                <td>Salaire Net:</td>
                                <td>{{ \Auth::user()->priceFormat($payslip->net_payble) }}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="text-right pt-2">
                        <h3 class="mt-2 mb-4">
                            <span class="border p-1 border-2 border-black mr-8 px-4">CONTRIBUTION IMPOSITION</span>
                        </h3>
                        <table class="ml-auto w-[300px]">
                            <tr>
                                <td>Abatt:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Abatt')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>Mont Imp:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Mont Impôt')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td>Impôt:</td>
                                <td>
                                    @foreach ( $deductions as $deduct)
                                        @if ($deduct['title'] == 'Ret Impot')
                                            {{ $deduct['amount'] }}
                                        @endif
                                    @endforeach                                    
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
    function saveAsPDF() {
        var element = document.getElementById('printableArea');
        var opt = {
            margin: 0.3,
            filename: '{{ $employee->name }}',
            image: {
                type: 'jpeg',
                quality: 1
            },
            html2canvas: {
                scale: 4,
                dpi: 72,
                letterRendering: true
            },
            jsPDF: {
                unit: 'in',
                format: 'A4'
            }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>