<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Mail\UserCreate;
use App\Models\User;
use App\Models\Utility;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesExport;
use App\Models\Allowance;
use App\Models\AllowanceOption;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\DeductionOption;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\NOC;
use App\Models\Termination;
use App\Models\ExperienceCertificate;
use App\Models\JoiningLetter;
use App\Models\ListImpot;
use App\Models\Loan;
use App\Models\LoanOption;
use App\Models\LoginDetail;
use App\Models\OtherPayment;
use App\Models\PaySlip;
use App\Models\SaturationDeduction;

//use Faker\Provider\File;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if (\Auth::user()->can('Manage Employee')) {
            if (Auth::user()->type == 'employee') {
                $employees = Employee::where('user_id', '=', Auth::user()->id)->get();
            } else {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->get();
            }

            return view('employee.index', compact('employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('Create Employee')) {
            $company_settings = Utility::settings();
            $documents        = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches         = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branches->prepend('Select Branch', '');
            $departments      = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations     = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employees        = User::where('created_by', \Auth::user()->creatorId())->get();

            $employeesId      = \Auth::user()->employeeIdFormat($this->employeeNumber());

            $grades = [
                'LT/COL' => 'LT/COL',
                'CDT' => 'CDT',
                'CNE' => 'CNE',
                'LT' => 'LT',
                'S/LT' => 'S/LT',
                'ASPIRANT' => 'ASPIRANT',
                'MAJOR' => 'MAJOR',
                'A/C' => 'A/C',
                'ADJ' => 'ADJ',
                'S/C' => 'S/C',
                'SGT' => 'SGT',
                'C/C' => 'C/C',
                'CAP' => 'CAP'
            ];

            $banks = [
                'EXIM' => 'EXIM',
                'BDCD' => 'BDCD',
                'BCIMR' => 'BCIMR',
                'IIB' => 'IIB',
                'IBB' => 'IBB',
                'CAC' => 'CAC',
                'SALAM BANK' => 'SALAM BANK',
                'SABA' => 'SABA',
                'EAST AFRICA' => 'EAST AFRICA',
                'BOA' => 'BOA'
            ];

            return view('employee.create', compact('employees', 'employeesId', 'departments', 'designations', 'documents', 'branches', 'grades', 'banks', 'company_settings'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('Create Employee')) {
            $default_language = \DB::table('settings')->select('value')->where('name', 'default_language')->first();
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'dob' => 'required',
                    'gender' => 'required',
                    'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8',
                    'address' => 'required',
                    'matricule' => 'required',
                    'indice' => 'required',
                    'etatcivil' => 'required',
                    /* 'email' => 'required|unique:users',
                    'password' => 'required', */
                    /* 'department_id' => 'required',
                    'designation_id' => 'required', */
                    'document.*' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->withInput()->with('error', $messages->first());
            }

            if ($request->hasFile('document')) {
                foreach ($request->document as $key => $document) {
                    $filenameWithExt = $request->file('document')[$key]->getClientOriginalName();
                    $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension       = $request->file('document')[$key]->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $dir             = 'uploads/document/';

                    $image_path      = $dir . $fileNameToStore;

                    if (\File::exists($image_path)) {
                        \File::delete($image_path);
                    }

                    $path = Utility::upload_coustom_file($request, 'document', $fileNameToStore, $dir, $key, []);

                    if ($path['flag'] == 1) {
                        $url = $path['url'];
                    } else {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                }
            }

            $user = User::create(
                [
                    'name' => $request['name'],
                    /* 'email' => $request['email'],
                    'password' => Hash::make($request['password']), */
                    'email' => "",
                    'password' => "",
                    'type' => 'employee',
                    'lang' => !empty($default_language) ? $default_language->value : 'en',
                    'created_by' => \Auth::user()->creatorId(),
                    'email_verified_at' => date("Y-m-d H:i:s"),
                ]
            );
            $user->save();
            $user->assignRole('Employee');


            if (!empty($request->document) && !is_null($request->document)) {
                $document_implode = implode(',', array_keys($request->document));
            } else {
                $document_implode = null;
            }

            $whole_indices = \DB::table('indices')->select('salary')->where('indice', '=', $request['indice'])->first();

            if ($whole_indices->salary == null) {
                return redirect()->back()->with('error', __('Indice introuvable !'));
            }

            $nbrenfant = isset($request['nbrenfant']) ? $request['nbrenfant'] : 0;

            $employee = Employee::create(
                [
                    'user_id' => $user->id,
                    'name' => $request['name'],
                    'dob' => $request['dob'],
                    'gender' => $request['gender'],
                    'grade' => $request['grade_id'],
                    'indice' => $request['indice'],
                    'echelle' => $request['echelle'],
                    'etatcivil' => $request['etatcivil'],
                    'nbrenfant' => $nbrenfant,
                    'salary' => $whole_indices->salary,
                    'phone' => $request['phone'],
                    'address' => $request['address'],
                    'matricule' => $request['matricule'],
                    'email' => $request['email'],
                    'password' => Hash::make($request['password']),
                    'employee_id' => $this->employeeNumber(),
                    'branch_id' => isset($request['branch_id']) ? $request['branch_id'] : "",
                    'department_id' => isset($request['department_id']) ? $request['department_id'] : "",
                    'designation_id' => isset($request['designation_id']) ? $request['designation_id'] : "",
                    'company_doj' => $request['company_doj'],
                    'documents' => $document_implode,
                    'account_holder_name' => $request['account_holder_name'],
                    'account_number' => $request['account_number'],
                    'bank_name' => $request['bank_name'],
                    'bank_identifier_code' => $request['bank_identifier_code'],
                    'branch_location' => $request['branch_location'],
                    'tax_payer_id' => $request['tax_payer_id'],
                    'created_by' => \Auth::user()->creatorId(),
                ]
            );

            if ($request->hasFile('document')) {
                foreach ($request->document as $key => $document) {
                    $filenameWithExt = $request->file('document')[$key]->getClientOriginalName();
                    $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension       = $request->file('document')[$key]->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $dir             = 'uploads/document/';

                    $image_path      = $dir . $fileNameToStore;

                    if (\File::exists($image_path)) {
                        \File::delete($image_path);
                    }

                    $path = Utility::upload_coustom_file($request, 'document', $fileNameToStore, $dir, $key, []);

                    if ($path['flag'] == 1) {
                        $url = $path['url'];
                    } else {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                    $employee_document = EmployeeDocument::create(
                        [
                            'employee_id' => $employee['employee_id'],
                            'document_id' => $key,
                            'document_value' => $fileNameToStore,
                            'created_by' => \Auth::user()->creatorId(),
                        ]
                    );
                    $employee_document->save();
                }
            }

            /* ------------------------------------------------ */ /* ------ Initialisation automatique ------ */

            $declared_allowance = AllowanceOption::all();
            $declared_deduction = DeductionOption::all();
            $declared_loans = LoanOption::all();

            $officers = ['S/LT', 'LT', 'CNE', 'CDT', 'LT/COL'];

            /* ------ Calcule des montants ------ */

            // Sujet Police
            $sujpol = \DB::table('tab_defaults')->where('name', 'SujetPolice')->first()->amount_1;

            // Prime
            $prime = 0;

             // Ind Medecin
             $indMedecin = 0;

            // MensuelleRespPart et Mission Special
            /* $menRP = in_array($request->grade, $officers) == false ? \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_2; */

            if (in_array($request->grade, $officers)) {
                $menRP = \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_2;
            } else {
                $menRP =  \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_1;
            }

            /*  $mSpe = in_array($request->grade, $officers) == false ? \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_2; */

            if (in_array($request->grade, $officers)) {
                $mSpe = \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_2;
            } else {
                $mSpe = \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_1;
            }

            // Salaire et allocations
            $salary_allowances = $whole_indices->salary + $sujpol + $menRP + $mSpe + $prime + $indMedecin;

            // CNR
            $db_cnr = intval(\DB::table('tab_defaults')->where('name', 'CNR')->first()->amount_1);
            $cnr = round(floatval(floatval($whole_indices->salary / 100) * $db_cnr));

            // Abatt
            $db_abatt = intval(\DB::table('tab_defaults')->where('name', 'Abatt')->first()->amount_1);
            $abatt = ($whole_indices->salary + $sujpol + $menRP + $mSpe) >= 80000 ? round(floatval((($whole_indices->salary + $sujpol + $menRP + $mSpe) / 100) * $db_abatt)) : 0;

            // Montant impôt
            $montimpot = $salary_allowances - $cnr - $abatt;

            // Retenu Impôt
            $retImp = ListImpot::select('montant')->where('tranche_basse', '<=', $montimpot)->where('tranche_haute', '>=', $montimpot)->first()->montant;

            // Retenu Medical
            $retMed = round(floatval(floatval(($salary_allowances - $abatt) / 100) * 2));

            /* ------ Enregistrement d'autre paiement ------ */

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employee->id;
            $new_otherPayment->title            = 'All,eau';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->amount           = $request['etatcivil'] == 'Célibataire' ? \DB::table('tab_defaults')->where('name', 'All,eau')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'All,eau')->first()->amount_2;
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employee->id;
            $new_otherPayment->title            = 'Press,Fam';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->amount           = $request['etatcivil'] == 'Célibataire' ? 0 : 1400 * $nbrenfant;
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employee->id;
            $new_otherPayment->title            = 'Pm forfaitaire';
            $new_otherPayment->amount           = '0';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employee->id;
            $new_otherPayment->title            = 'PFranc';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->amount           = \DB::table('tab_defaults')->where('name', 'Pfranc')->first()->amount_1;
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            /* ------ Enregistrement des crédits ------ */

            foreach ($declared_loans as $loan) {
                switch ($loan->id) {
                    case '1':
                        $new_loan                   = new Loan();
                        $new_loan->employee_id      = $employee->id;
                        $new_loan->title            = $loan->name;
                        $new_loan->loan_option      = '1';
                        $new_loan->amount           = '0';
                        $new_loan->type           = 'fixed';
                        $new_loan->created_by       = \Auth::user()->creatorId();
                        $new_loan->save();
                        break;
                }
            }

            /* ------ Enregistrement des allocations ------ */

            foreach ($declared_allowance as $allowance) {
                switch ($allowance->id) {
                    case '2':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '2';
                        $new_allowance->amount           = $sujpol;
                        $new_allowance->type             = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '3':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '3';
                        $new_allowance->amount           = $menRP;
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '4':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '4';
                        $new_allowance->amount           = $mSpe;
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '5':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '5';
                        $new_allowance->amount           = '0';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '6':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employee->id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '6';
                        $new_allowance->amount           = '0';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;
                }
            }

            /* ------ Enregistrement des déductions ------ */

            foreach ($declared_deduction as $deduction) {
                switch ($deduction->id) {
                    case '1':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '1';
                        $new_deduction->amount           = $cnr;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '2':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '2';
                        $new_deduction->amount           = $abatt;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '3':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '3';
                        $new_deduction->amount           = $montimpot;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '4':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '4';
                        $new_deduction->amount           = $retImp;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '5':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '5';
                        $new_deduction->amount           = '400';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '6':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '6';
                        $new_deduction->amount           = $retMed;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '7':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '7';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '8':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '8';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '9':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '9';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '10':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '10';
                        $new_deduction->amount           = '1000';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '11':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '11';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '12':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '12';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '13':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '13';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '14':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employee->id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '14';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;
                }
            }

            /* ------------------------------------------------ */

            $setings = Utility::settings();
            if ($setings['new_employee'] == 1) {
                $department = Department::find($request['department_id']);
                $branch = Branch::find($request['branch_id']);
                $designation = Designation::find($request['designation_id']);
                $uArr = [
                    'employee_email' => $user->email,
                    'employee_password' => $request->password,
                    'employee_name' => $request['name'],
                    'employee_branch' => !empty($branch->name) ? $branch->name : '',
                    'department_id' => !empty($department->name) ? $department->name : '',
                    'designation_id' => !empty($designation->name) ? $designation->name : '',
                ];
                $resp = Utility::sendEmailTemplate('new_employee', [$user->id => $user->email], $uArr);

                return redirect()->route('employee.index')->with('success', __('Employee successfully created.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            return redirect()->route('employee.index')->with('success', __('Employee  successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        if (\Auth::user()->can('Edit Employee')) {
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee     = Employee::find($id);
            $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);

            $grades = [
                'LT/COL' => 'LT/COL',
                'CDT' => 'CDT',
                'CNE' => 'CNE',
                'LT' => 'LT',
                'S/LT' => 'S/LT',
                'ASPIRANT' => 'ASPIRANT',
                'MAJOR' => 'MAJOR',
                'A/C' => 'A/C',
                'ADJ' => 'ADJ',
                'S/C' => 'S/C',
                'SGT' => 'SGT',
                'C/C' => 'C/C',
                'CAP' => 'CAP'
            ];

            $banks = [
                'EXIM' => 'EXIM',
                'BDCD' => 'BDCD',
                'BCIMR' => 'BCIMR',
                'IIB' => 'IIB',
                'IBB' => 'IBB',
                'CAC' => 'CAC',
                'SALAM BANK' => 'SALAM BANK',
                'SABA' => 'SABA',
                'EAST AFRICA' => 'EAST AFRICA',
                'BOA' => 'BOA'
            ];

            $current_bank = $employee->bank_name;

            return view('employee.edit', compact('employee', 'employeesId', 'grades', 'banks', 'current_bank', 'branches', 'departments', 'designations', 'documents'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('Edit Employee')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'dob' => 'required',
                    'gender' => 'required',
                    'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                    'address' => 'required',
                    'matricule' => 'required',
                    'document.*' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $employee = Employee::findOrFail($id);

            if ($request->document) {
                foreach ($request->document as $key => $document) {
                    if (!empty($document)) {


                        $filenameWithExt = $request->file('document')[$key]->getClientOriginalName();
                        $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension       = $request->file('document')[$key]->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                        $dir             = 'uploads/document/';

                        $image_path      = $dir . $fileNameToStore;

                        if (\File::exists($image_path)) {
                            \File::delete($image_path);
                        }

                        $path = \Utility::upload_coustom_file($request, 'document', $fileNameToStore, $dir, $key, []);

                        if ($path['flag'] == 1) {
                            $url = $path['url'];
                        } else {
                            return redirect()->back()->with('error', __($path['msg']));
                        }

                        $employee_document = EmployeeDocument::where('employee_id', $employee->employee_id)->where('document_id', $key)->first();

                        if (!empty($employee_document)) {
                            if ($employee_document->document_value) {
                                \File::delete(storage_path('uploads/document/' . $employee_document->document_value));
                            }
                            $employee_document->document_value = $fileNameToStore;
                            $employee_document->save();
                        } else {
                            $employee_document                 = new EmployeeDocument();
                            $employee_document->employee_id    = $employee->employee_id;
                            $employee_document->document_id    = $key;
                            $employee_document->document_value = $fileNameToStore;
                            $employee_document->save();
                        }
                    }
                }
            }

            // Lire les indices
            $whole_indices = \DB::table('indices')->select('salary')->where('indice', '=', $request['indice'])->first();

            if ($whole_indices == null) {
                return redirect()->back()->with('error', __('Indice introuvable !'));
            }

            // Enregistrer les modifications
            $employee = Employee::findOrFail($id);
            // Modifier son salaire selon son indice
            $request['salary'] = $whole_indices->salary;
            $input    = $request->all();
            $employee->fill($input)->save();

            // Si l'employer est enregistrer avec succès

            $officers = ['S/LT', 'LT', 'CNE', 'CDT', 'LT/COL'];

            /* ------ Calcule des montants ------ */

            // Sujet Police
            $sujpol = \DB::table('tab_defaults')->where('name', 'SujetPolice')->first()->amount_1;            

            // Prime
            $prime = \DB::table('allowances')->where('employee_id', $employee['employee_id'])->where('allowance_option', 5)->first()->amount;

             // Ind Medecin
             $indMedecin = \DB::table('allowances')->where('employee_id', $employee['employee_id'])->where('allowance_option', 6)->first()->amount;

            // MensuelleRespPart et Mission Special
            /* $menRP = in_array($request->grade, $officers) == false ? \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_2; */

            if (in_array($request->grade, $officers)) {
                $menRP = \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_2;
            } else {
                $menRP =  \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_1;
            }

            /*  $mSpe = in_array($request->grade, $officers) == false ? \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_2; */

            if (in_array($request->grade, $officers)) {
                $mSpe = \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_2;
            } else {
                $mSpe = \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_1;
            }

            // Salaire et allocations
            $salary_allowances = $whole_indices->salary + $sujpol + $menRP + $mSpe + $prime + $indMedecin;

            // CNR
            $db_cnr = intval(\DB::table('tab_defaults')->where('name', 'CNR')->first()->amount_1);
            $cnr = round(floatval(floatval($whole_indices->salary / 100) * $db_cnr));

            // Abatt
            $db_abatt = intval(\DB::table('tab_defaults')->where('name', 'Abatt')->first()->amount_1);
            $abatt = ($whole_indices->salary + $sujpol + $menRP + $mSpe) >= 80000 ? round(floatval((($whole_indices->salary + $sujpol + $menRP + $mSpe) / 100) * $db_abatt)) : 0;

            // Montant impôt
            $montimpot = $salary_allowances - $cnr - $abatt;

            // Retenu Impôt
            $retImp = ListImpot::select('montant')->where('tranche_basse', '<=', $montimpot)->where('tranche_haute', '>=', $montimpot)->first()->montant;

            // Retenu Medical
            $retMed = round(floatval(floatval(($salary_allowances - $abatt) / 100) * 2));

            // Deductions
            SaturationDeduction::where('employee_id', $id)->where('deduction_option', 1)->update([
                'amount' => $cnr
            ]);
            SaturationDeduction::where('employee_id', $id)->where('deduction_option', 2)->update([
                'amount' => $abatt
            ]);
            SaturationDeduction::where('employee_id', $id)->where('deduction_option', 4)->update([
                'amount' => $retImp
            ]);
            SaturationDeduction::where('employee_id', $id)->where('deduction_option', 6)->update([
                'amount' => $retMed
            ]);
            SaturationDeduction::where('employee_id', $id)->where('deduction_option', 3)->update([
                'amount' => $montimpot
            ]);

            /* ------ Enregistrement d'autre paiement ------ */

            $nbrenfant = isset($request['nbrenfant']) ? $request['nbrenfant'] : 0;

            OtherPayment::where('employee_id', $id)->where('title', 'All,eau')->update([
                'amount' => $request['etatcivil'] == 'Célibataire' ? \DB::table('tab_defaults')->where('name', 'All,eau')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'All,eau')->first()->amount_2
            ]);

            OtherPayment::where('employee_id', $id)->where('title', 'Press,Fam')->update([
                'amount' => $request['etatcivil'] == 'Célibataire' ? 0 : 1400 * $nbrenfant
            ]);

            // Allocations
            Allowance::where('employee_id', $id)->where('allowance_option', 3)->update([
                'amount' => $menRP
            ]);
            Allowance::where('employee_id', $id)->where('allowance_option', 4)->update([
                'amount' => $mSpe
            ]);
            // die(json_encode(($whole_indices->salary + $sujpol + $menRP + $mSpe)));

            return redirect()->route('setsalary.index')->with('success', 'Employee successfully updated.');

            if (\Auth::user()->type != 'employee') {
                return redirect()->route('employee.index')->with('success', 'Employee successfully updated.');
            } else {
                return redirect()->route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))->with('success', 'Employee successfully updated.');
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {

        if (Auth::user()->can('Delete Employee')) {
            $employee      = Employee::findOrFail($id);
            $user          = User::where('id', '=', $employee->user_id)->first();
            $ContractEmployee = Contract::where('employee_name', '=', $employee->user_id)->get();
            $emp_documents = EmployeeDocument::where('employee_id', $employee->employee_id)->get();
            $payslips = PaySlip::where('employee_id', $id)->get();

            $employee->delete();
            $user->delete();
            foreach ($payslips as $payslip) {
                $payslip->delete();
            }
            foreach ($ContractEmployee as $contractdelete) {
                $contractdelete->delete();
            }

            $dir = storage_path('uploads/document/');
            foreach ($emp_documents as $emp_document) {
                $emp_document->delete();
                \File::delete(storage_path('uploads/document/' . $emp_document->document_value));
                if (!empty($emp_document->document_value)) {
                    // unlink($dir . $emp_document->document_value);
                }
            }

            return redirect()->route('employee.index')->with('success', 'Employee successfully deleted.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        if (\Auth::user()->can('Show Employee')) {
            $empId        = Crypt::decrypt($id);
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee     = Employee::find($empId);
            $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);

            return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    // public function json(Request $request)
    // {
    //     $designations = Designation::where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();

    //     return response()->json($designations);
    // }

    function employeeNumber()
    {
        $latest = Employee::where('created_by', '=', \Auth::user()->creatorId())->latest('id')->first();
        if (!$latest) {
            return 1;
        }

        return $latest->employee_id + 1;
    }

    public function profile(Request $request)
    {
        if (\Auth::user()->can('Manage Employee Profile')) {
            $employees = Employee::where('created_by', \Auth::user()->creatorId());
            if (!empty($request->branch)) {
                $employees->where('branch_id', $request->branch);
            }
            if (!empty($request->department)) {
                $employees->where('department_id', $request->department);
            }
            if (!empty($request->designation)) {
                $employees->where('designation_id', $request->designation);
            }
            $employees = $employees->get();

            $brances = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $brances->prepend('All', '0');

            $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments->prepend('All', '0');

            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations->prepend('All', '0');

            return view('employee.profile', compact('employees', 'departments', 'designations', 'brances'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function profileShow($id)
    {
        if (\Auth::user()->can('Show Employee Profile')) {
            try {
                $empId        = \Illuminate\Support\Facades\Crypt::decrypt($id);
            } catch (\RuntimeException $e) {
                return redirect()->back()->with('error', __('Employee not avaliable'));
            }
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee     = Employee::find($empId);
            if ($employee == null) {
                $employee     = Employee::where('user_id', $empId)->first();
            }
            $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);

            return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function lastLogin(Request $request)
    {
        $users = User::where('created_by', \Auth::user()->creatorId())->get();

        $time = date_create($request->month);
        $firstDayofMOnth = (date_format($time, 'Y-m-d'));
        $lastDayofMonth =    \Carbon\Carbon::parse($request->month)->endOfMonth()->toDateString();
        $objUser = \Auth::user();
        // $currentlocation = User::userCurrentLocation();
        $usersList = User::where('created_by', '=', $objUser->creatorId())
            ->whereNotIn('type', ['super admin', 'company'])->get()->pluck('name', 'id');
        $usersList->prepend('All', '');
        if ($request->month == null) {
            $userdetails = \DB::table('login_details')
                ->join('users', 'login_details.user_id', '=', 'users.id')
                ->select(\DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
                ->where(['login_details.created_by' => \Auth::user()->creatorId()])
                ->whereMonth('date', date('m'))->whereYear('date', date('Y'));
        } else {
            $userdetails = \DB::table('login_details')
                ->join('users', 'login_details.user_id', '=', 'users.id')
                ->select(\DB::raw('login_details.*, users.id as user_id , users.name as user_name , users.email as user_email ,users.type as user_type'))
                ->where(['login_details.created_by' => \Auth::user()->creatorId()]);
        }
        if (!empty($request->month)) {
            $userdetails->where('date', '>=', $firstDayofMOnth);
            $userdetails->where('date', '<=', $lastDayofMonth);
        }
        if (!empty($request->employee)) {
            $userdetails->where(['user_id'  => $request->employee]);
        }
        $userdetails = $userdetails->get();

        return view('employee.lastLogin', compact('users', 'usersList', 'userdetails'));
    }

    public function view($id)
    {
        $users = LoginDetail::find($id);
        return view('employee.user_log', compact('users'));
    }

    public function logindestroy($id)
    {
        $employee = LoginDetail::where('user_id', $id)->delete();

        return redirect()->back()->with('success', 'Employee successfully deleted.');
    }

    public function employeeJson(Request $request)
    {
        $employees = Employee::where('branch_id', $request->branch)->get()->pluck('name', 'id')->toArray();

        return response()->json($employees);
    }
    public function importFile()
    {
        return view('employee.import');
    }

    public function import(Request $request)
    {
        $rules = [
            'file' => 'required|mimes:csv,txt',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $employees = (new EmployeesImport())->toArray(request()->file('file'))[0];
        $totalCustomer = count($employees) - 1;
        $errorArray    = [];

        for ($i = 1; $i <= count($employees) - 1; $i++) {

            $employee = $employees[$i];


            // Lire les indices
            $whole_indices = \DB::table('indices')->select('salary')->where('indice', '=', $employee[5])->first();
            if ($whole_indices == null) {
                \DB::table('employees')->truncate();
                return redirect()->back()->with('error', __('Indice introuvable !'));
            }

            /* $employeeByEmail = Employee::where('email', $employee[5])->first();
            $userByEmail = User::where('email', $employee[5])->first();


            if (!empty($employeeByEmail) && !empty($userByEmail)) {
                $employeeData = $employeeByEmail;
            } else {

                
            } */
            $user = new User();
            $user->name = $employee[0];
            $user->email = ""; // $employee[5];
            $user->password = ""; // Hash::make($employee[6]);
            $user->type = 'employee';
            $user->lang = 'en';
            $user->created_by = \Auth::user()->creatorId();
            $user->email_verified_at = date("Y-m-d H:i:s");
            $user->save();
            $user->assignRole('Employee');

            $employeeData = new Employee();
            $employeeData->employee_id      = $this->employeeNumber();
            $employeeData->user_id             = $user->id;


            $employeeData->name                = $employee[0];
            $employeeData->dob                 = $employee[1];
            $employeeData->gender              = $employee[2];
            $employeeData->phone               = $employee[3];
            $employeeData->grade             = $employee[4];
            $employeeData->indice             = $employee[5];
            $employeeData->echelle             = $employee[6];
            $employeeData->address             = $employee[7];
            $employeeData->etatcivil             = $employee[8];
            $employeeData->nbrenfant             = $employee[9];
            $employeeData->email               = ""; // $employee[5];
            $employeeData->salary               = $whole_indices->salary;
            $employeeData->password            = ""; // Hash::make($employee[6]);
            $employeeData->employee_id         = $this->employeeNumber();
            $employeeData->branch_id           = ""; // $employee[8];
            $employeeData->department_id       = ""; // $employee[9];
            $employeeData->designation_id      = ""; // $employee[10];
            $employeeData->company_doj         = $employee[8];
            $employeeData->account_holder_name = ""; // $employee[12];
            $employeeData->account_number      = ""; // $employee[13];
            $employeeData->bank_name           = $employee[9];
            $employeeData->bank_identifier_code = ""; // $employee[15];
            $employeeData->branch_location     = ""; // $employee[16];
            $employeeData->tax_payer_id        = ""; // $employee[17];
            $employeeData->created_by          = \Auth::user()->creatorId();

            $nbrenfant = isset($employee[9]) ? $employee[9] : 0;

            if (empty($employeeData)) {
                $errorArray[] = $employeeData;
            } else {
                $employeeData->save();
            }

            /* ------------------------------------------------ */ /* ------ Initialisation automatique ------ */

            $declared_allowance = AllowanceOption::all();
            $declared_deduction = DeductionOption::all();
            $declared_loans = LoanOption::all();

            $officers = ['S/LT', 'LT', 'CNE', 'CDT', 'LT/COL'];

            /* ------ Calcule des montants ------ */

            // Sujet Police
            $sujpol = \DB::table('tab_defaults')->where('name', 'SujetPolice')->first()->amount_1;

            // Prime
            $prime = \DB::table('allowances')->where('employee_id', $this->employeeNumber())->where('allowance_option', 5)->first()->amount;

             // Ind Medecin
             $indMedecin = \DB::table('allowances')->where('employee_id', $this->employeeNumber())->where('allowance_option', 6)->first()->amount;

            // MensuelleRespPart et Mission Special
            /* $menRP = in_array($request->grade, $officers) == false ? \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_2; */

            if (in_array($request->grade, $officers)) {
                $menRP = \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_2;
            } else {
                $menRP =  \DB::table('tab_defaults')->where('name', 'Mensuelle RespPart')->first()->amount_1;
            }

            /*  $mSpe = in_array($request->grade, $officers) == false ? \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_2; */

            if (in_array($request->grade, $officers)) {
                $mSpe = \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_2;
            } else {
                $mSpe = \DB::table('tab_defaults')->where('name', 'Mission Special')->first()->amount_1;
            }

            // Salaire et allocations
            $salary_allowances = $whole_indices->salary + $sujpol + $menRP + $mSpe + $prime + $indMedecin;

            // CNR
            $db_cnr = intval(\DB::table('tab_defaults')->where('name', 'CNR')->first()->amount_1);
            $cnr = round(floatval(floatval($whole_indices->salary / 100) * $db_cnr));

            // Abatt
            $db_abatt = intval(\DB::table('tab_defaults')->where('name', 'Abatt')->first()->amount_1);
            $abatt = round(floatval((($whole_indices->salary + $sujpol + $menRP + $mSpe) / 100) * $db_abatt));

            // Montant impôt
            $montimpot = $salary_allowances - $cnr - $abatt;

            // Retenu Impôt
            $retImp = ListImpot::select('montant')->where('tranche_basse', '<=', $montimpot)->where('tranche_haute', '>=', $montimpot)->first()->montant;

            // Retenu Medical
            $retMed = round(floatval(floatval(($salary_allowances - $abatt) / 100) * 2));

            /* ------ Enregistrement d'autre paiement ------ */

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employeeData->employee_id;
            $new_otherPayment->title            = 'All,eau';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->amount           = $employeeData[8] == 'Célibataire' ? \DB::table('tab_defaults')->where('name', 'All,eau')->first()->amount_1 : \DB::table('tab_defaults')->where('name', 'All,eau')->first()->amount_2;
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employeeData->employee_id;
            $new_otherPayment->title            = 'Press,Fam';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->amount           = $request['etatcivil'] == 'Célibataire' ? 0 : 1400 * $nbrenfant;
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employeeData->employee_id;
            $new_otherPayment->title            = 'Pm forfaitaire';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->amount           = '0';
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            $new_otherPayment                   = new OtherPayment();
            $new_otherPayment->employee_id      = $employeeData->employee_id;
            $new_otherPayment->title            = 'PFranc';
            $new_otherPayment->type           = 'fixed';
            $new_otherPayment->amount           = \DB::table('tab_defaults')->where('name', 'Pfranc')->first()->amount_1;
            $new_otherPayment->created_by       = \Auth::user()->creatorId();
            $new_otherPayment->save();

            /* ------ Enregistrement des crédits ------ */

            foreach ($declared_loans as $loan) {
                switch ($loan->id) {
                    case '1':
                        $new_loan                   = new Loan();
                        $new_loan->employee_id      = $employeeData->employee_id;
                        $new_loan->title            = $loan->name;
                        $new_loan->loan_option      = '1';
                        $new_loan->amount           = '0';
                        $new_loan->type           = 'fixed';
                        $new_loan->created_by       = \Auth::user()->creatorId();
                        $new_loan->save();
                        break;
                }
            }

            /* ------ Enregistrement des allocations ------ */

            foreach ($declared_allowance as $allowance) {
                switch ($allowance->id) {
                    case '2':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employeeData->employee_id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '2';
                        $new_allowance->amount           = $sujpol;
                        $new_allowance->type             = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '3':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employeeData->employee_id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '3';
                        $new_allowance->amount           = $menRP;
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '4':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employeeData->employee_id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '4';
                        $new_allowance->amount           = $mSpe;
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '5':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employeeData->employee_id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '5';
                        $new_allowance->amount           = '0';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;

                    case '6':
                        $new_allowance                   = new Allowance();
                        $new_allowance->employee_id      = $employeeData->employee_id;
                        $new_allowance->title            = $allowance->name;
                        $new_allowance->allowance_option      = '6';
                        $new_allowance->amount           = '0';
                        $new_allowance->type           = 'fixed';
                        $new_allowance->created_by       = \Auth::user()->creatorId();
                        $new_allowance->save();
                        break;
                }
            }

            /* ------ Enregistrement des déductions ------ */

            foreach ($declared_deduction as $deduction) {
                switch ($deduction->id) {
                    case '1':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '1';
                        $new_deduction->amount           = $cnr;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '2':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '2';
                        $new_deduction->amount           = $abatt;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '3':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '18';
                        $new_deduction->amount           = $montimpot;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '4':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '4';
                        $new_deduction->amount           = $retImp;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '5':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '5';
                        $new_deduction->amount           = '400';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '6':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '6';
                        $new_deduction->amount           = $retMed;
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '7':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '7';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '8':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '8';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '9':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '9';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '10':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '10';
                        $new_deduction->amount           = '1000';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '11':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '11';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '12':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '12';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '13':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '13';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;

                    case '14':
                        $new_deduction                   = new SaturationDeduction();
                        $new_deduction->employee_id      = $employeeData->employee_id;
                        $new_deduction->title            = $deduction->name;
                        $new_deduction->deduction_option      = '14';
                        $new_deduction->amount           = '0';
                        $new_deduction->type           = 'fixed';
                        $new_deduction->created_by       = \Auth::user()->creatorId();
                        $new_deduction->save();
                        break;
                }
            }

            /* ------------------------------------------------ */
        }


        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg']    = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalCustomer . ' ' . 'record');


            foreach ($errorArray as $errorData) {

                $errorRecord[] = implode(',', $errorData);
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    public function export()
    {
        $name = 'employee_' . date('Y-m-d i:h:s');
        $data = Excel::download(new EmployeesExport(), $name . '.xlsx');

        return $data;
    }
    public function joiningletterPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter = JoiningLetter::where('lang', $currantLang)->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' => !empty($employees->address) ? $employees->address : '',
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'start_date' => !empty($employees->company_doj) ? $employees->company_doj : '',
            'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
            'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
            'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
            'total_hours' => $result,
        ];

        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterpdf', compact('joiningletter', 'employees'));
    }
    public function joiningletterDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter = JoiningLetter::where('lang', $currantLang)->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);



        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),

            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' => !empty($employees->address) ? $employees->address : '',
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'start_date' => !empty($employees->company_doj) ? $employees->company_doj : '',
            'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
            'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
            'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
            'total_hours' => $result,
            //

        ];
        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterdocx', compact('joiningletter', 'employees'));
    }

    public function ExpCertificatePdf($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id', $id)->first();
        $experience_certificate = ExperienceCertificate::where('lang', $currantLang)->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff  = date_diff($date1, $date2);
        $duration = $diff->format("%a days");

        if (!empty($termination->termination_date)) {

            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                'duration' => $duration,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',

            ];
        } else {
            return redirect()->back()->with('error', __('Termination date is required.'));
        }


        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatepdf', compact('experience_certificate', 'employees'));
    }
    public function ExpCertificateDoc($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id', $id)->first();
        $experience_certificate = ExperienceCertificate::where('lang', $currantLang)->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff  = date_diff($date1, $date2);
        $duration = $diff->format("%a days");
        if (!empty($termination->termination_date)) {
            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                'duration' => $duration,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',

            ];
        } else {
            return redirect()->back()->with('error', __('Termination date is required.'));
        }

        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatedocx', compact('experience_certificate', 'employees'));
    }
    public function NocPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate = NOC::where('lang', $currantLang)->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'employee_name' => $employees->name,
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'app_name' => env('APP_NAME'),
        ];

        $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocpdf', compact('noc_certificate', 'employees'));
    }
    public function NocDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate = NOC::where('lang', $currantLang)->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'employee_name' => $employees->name,
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'app_name' => env('APP_NAME'),
        ];

        $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocdocx', compact('noc_certificate', 'employees'));
    }

    public function getdepartment(Request $request)
    {
        if ($request->branch_id == 0) {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }
        return response()->json($departments);
    }

    public function json(Request $request)
    {
        if ($request->department_id == 0) {
            $designations = Designation::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        }
        $designations = Designation::where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();

        return response()->json($designations);
    }
}
