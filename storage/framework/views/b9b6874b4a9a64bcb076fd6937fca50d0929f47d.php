<?php
// $logo = asset(Storage::url('uploads/logo/'));
$logo = \App\Models\Utility::get_file('uploads/logo/');

$company_logo = Utility::getValByName('company_logo');
?>
<?php
$otherPayments = $payslipDetail['earning']['otherPayment'][0]->other_payment;
$data = [];
?>
<script src="https://cdn.tailwindcss.com"></script>

<style>
    * {
        font-size: 15px;
    }
</style>

<div class="modal-body">
    <div class="text-md-end mb-2">
        <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo e(__('Download')); ?>" onclick="saveAsPDF()"><span class="fa fa-download"></span></a>
        <?php if(\Auth::user()->type == 'company' || \Auth::user()->type == 'hr'): ?>
        <a title="Mail Send" href="<?php echo e(route('payslip.send', [$employee->id, $payslip->salary_month])); ?>" class="btn btn-sm btn-warning"><span class="fa fa-paper-plane"></span></a>
        <?php endif; ?>
    </div>
    <!-- <div class="invoice" id="printableArea">
        <div class="row">
            <div class="col-form-label">
                <div class="invoice-number">
                    <img src="<?php echo e($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'dark_logo.png')); ?>"
                        width="170px;">
                </div>


                <div class="invoice-print">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="invoice-title">
                                

                            </div>
                            <hr>
                            <div class="row text-sm">
                                <div class="col-md-6">
                                    <address>
                                        <strong><?php echo e(__('Name')); ?> :</strong> <?php echo e($employee->name); ?><br>
                                        <strong><?php echo e(__('Position')); ?> :</strong> <?php echo e(__('Employee')); ?><br>
                                        <strong><?php echo e(__('Salary Date')); ?> :</strong>
                                        <?php echo e(\Auth::user()->dateFormat($payslip->created_at)); ?><br>
                                    </address>
                                </div>
                                <div class="col-md-6 text-end">
                                    <address>
                                        <strong><?php echo e(\Utility::getValByName('company_name')); ?> </strong><br>
                                        <?php echo e(\Utility::getValByName('company_address')); ?> ,
                                        <?php echo e(\Utility::getValByName('company_city')); ?>,<br>
                                        <?php echo e(\Utility::getValByName('company_state')); ?>-<?php echo e(\Utility::getValByName('company_zipcode')); ?><br>
                                        <strong><?php echo e(__('Salary Slip')); ?> :</strong> <?php echo e($payslip->salary_month); ?><br>
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
                                            <th><?php echo e(__('Earning')); ?></th>
                                            <th><?php echo e(__('Title')); ?></th>
                                            <th><?php echo e(__('Type')); ?></th>
                                            <th class="text-right"><?php echo e(__('Amount')); ?></th>
                                        </tr>
                                        <tr>
                                            <td><?php echo e(__('Basic Salary')); ?></td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td class="text-right">
                                                <?php echo e(\Auth::user()->priceFormat($payslip->basic_salary)); ?></td>
                                        </tr>

                                        <?php $__currentLoopData = $payslipDetail['earning']['allowance']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allowance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $employess = \App\Models\Employee::find($allowance->employee_id);
                                                $allowance = json_decode($allowance->allowance);
                                            ?>
                                            <?php $__currentLoopData = $allowance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $all): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(__('Allowance')); ?></td>
                                                    <td><?php echo e($all->title); ?></td>
                                                    <td><?php echo e(ucfirst($all->type)); ?></td>
                                                    <?php if($all->type != 'percentage'): ?>
                                                        <td class="text-right">
                                                            <?php echo e(\Auth::user()->priceFormat($all->amount)); ?></td>
                                                    <?php else: ?>
                                                        <td class="text-right"><?php echo e($all->amount); ?>%
                                                            (<?php echo e(\Auth::user()->priceFormat(($all->amount * $payslip->basic_salary) / 100)); ?>)
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <?php $__currentLoopData = $payslipDetail['earning']['commission']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $commission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $employess = \App\Models\Employee::find($commission->employee_id);
                                                $commissions = json_decode($commission->commission);
                                            ?>
                                            <?php $__currentLoopData = $commissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $empcom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(__('Commission')); ?></td>
                                                    <td><?php echo e($empcom->title); ?></td>
                                                    <td><?php echo e(ucfirst($empcom->type)); ?></td>
                                                    <?php if($empcom->type != 'percentage'): ?>
                                                        <td class="text-right">
                                                            <?php echo e(\Auth::user()->priceFormat($empcom->amount)); ?></td>
                                                    <?php else: ?>
                                                        <td class="text-right"><?php echo e($empcom->amount); ?>%
                                                            (<?php echo e(\Auth::user()->priceFormat(($empcom->amount * $payslip->basic_salary) / 100)); ?>)
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <?php $__currentLoopData = $payslipDetail['earning']['otherPayment']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $otherPayment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $employess = \App\Models\Employee::find($otherPayment->employee_id);
                                                $otherpay = json_decode($otherPayment->other_payment);
                                            ?>
                                            <?php $__currentLoopData = $otherpay; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(__('Other Payment')); ?></td>
                                                    <td><?php echo e($op->title); ?></td>
                                                    <td><?php echo e(ucfirst($op->type)); ?></td>
                                                    <?php if($op->type != 'percentage'): ?>
                                                        <td class="text-right">
                                                            <?php echo e(\Auth::user()->priceFormat($op->amount)); ?></td>
                                                    <?php else: ?>
                                                        <td class="text-right"><?php echo e($op->amount); ?>%
                                                            (<?php echo e(\Auth::user()->priceFormat(($op->amount * $payslip->basic_salary) / 100)); ?>)
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <?php $__currentLoopData = $payslipDetail['earning']['overTime']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $overTime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $arrayJson = json_decode($overTime->overtime);
                                                foreach ($arrayJson as $key => $overtime) {
                                                    foreach ($arrayJson as $key => $overtimes) {
                                                        $overtitle = $overtimes->title;
                                                        $OverTime = $overtimes->number_of_days * $overtimes->hours * $overtimes->rate;
                                                    }
                                                }
                                            ?>
                                            <?php $__currentLoopData = $arrayJson; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $overtime): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(__('OverTime')); ?></td>
                                                    <td><?php echo e($overtime->title); ?></td>
                                                    <td>-</td>
                                                    <td class="text-right">
                                                        <?php echo e(\Auth::user()->priceFormat($overtime->number_of_days * $overtime->hours * $overtime->rate)); ?>

                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-md">
                                    <tbody>
                                        <tr class="font-weight-bold">
                                            <th><?php echo e(__('Deduction')); ?></th>
                                            <th><?php echo e(__('Title')); ?></th>
                                            <th><?php echo e(__('type')); ?></th>
                                            <th class="text-right"><?php echo e(__('Amount')); ?></th>
                                        </tr>

                                        <?php $__currentLoopData = $payslipDetail['deduction']['loan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $employess = \App\Models\Employee::find($loan->employee_id);
                                                $loans = json_decode($loan->loan);
                                            ?>
                                            <?php $__currentLoopData = $loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emploanss): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(__('Loan')); ?></td>
                                                    <td><?php echo e($emploanss->title); ?></td>
                                                    <td><?php echo e(ucfirst($emploanss->type)); ?></td>
                                                    <?php if($emploanss->type != 'percentage'): ?>
                                                        <td class="text-right">
                                                            <?php echo e(\Auth::user()->priceFormat($emploanss->amount)); ?></td>
                                                    <?php else: ?>
                                                        <td class="text-right"><?php echo e($emploanss->amount); ?>%
                                                            (<?php echo e(\Auth::user()->priceFormat(($emploanss->amount * $payslip->basic_salary) / 100)); ?>)
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <?php $__currentLoopData = $payslipDetail['deduction']['deduction']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $employess = \App\Models\Employee::find($deduction->employee_id);
                                                $deductions = json_decode($deduction->saturation_deduction);
                                            ?>
                                            <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $saturationdeduc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e(__('Saturation Deduction')); ?></td>
                                                    <td><?php echo e($saturationdeduc->title); ?></td>
                                                    <td><?php echo e(ucfirst($saturationdeduc->type)); ?></td>
                                                    <?php if($saturationdeduc->type != 'percentage'): ?>
                                                        <td class="text-right">
                                                            <?php echo e(\Auth::user()->priceFormat($saturationdeduc->amount)); ?>

                                                        </td>
                                                    <?php else: ?>
                                                        <td class="text-right"><?php echo e($saturationdeduc->amount); ?>%
                                                            (<?php echo e(\Auth::user()->priceFormat(($saturationdeduc->amount * $payslip->basic_salary) / 100)); ?>)
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-4">
                                <div class="col-lg-8">

                                </div>
                                <div class="col-lg-4 text-right text-sm">
                                    <div class="invoice-detail-item pb-2">
                                        <div class="invoice-detail-name font-weight-bold"><?php echo e(__('Total Earning')); ?>

                                        </div>
                                        <div class="invoice-detail-value">
                                            <?php echo e(\Auth::user()->priceFormat($payslipDetail['totalEarning'])); ?></div>
                                    </div>
                                    <div class="invoice-detail-item">
                                        <div class="invoice-detail-name font-weight-bold"><?php echo e(__('Total Deduction')); ?>

                                        </div>
                                        <div class="invoice-detail-value">
                                            <?php echo e(\Auth::user()->priceFormat($payslipDetail['totalDeduction'])); ?></div>
                                    </div>
                                    <hr class="mt-2 mb-2">
                                    <div class="invoice-detail-item">
                                        <div class="invoice-detail-name font-weight-bold"><?php echo e(__('Net Salary')); ?></div>
                                        <div class="invoice-detail-value invoice-detail-value-lg">
                                            <?php echo e(\Auth::user()->priceFormat($payslip->net_payble)); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-md-right pb-2 text-sm">
                    <div class="float-lg-left mb-lg-0 mb-3 ">
                        <p class="mt-2"><?php echo e(__('Employee Signature')); ?></p>
                    </div>
                    <p class="mt-2 "> <?php echo e(__('Paid By')); ?></p>
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
                    <td>Matricule: &nbsp; <?php echo e($employee->matricule); ?></td>
                    <td>Status: &nbsp; Fonctionnaire</td>
                </tr>
                <tr>
                    <td>Nom: &nbsp; <?php echo e($employee->name); ?></td>
                    <td>Indice: &nbsp; <?php echo e($employee->indice); ?></td>
                </tr>
                <tr>
                    <td>Administration: &nbsp; MINISTERE DE L'EQUIPEMENT</td>
                    <td>Echelle: &nbsp; <?php echo e($employee->echelle); ?></td>
                </tr>
                <tr>
                    <td>Service: &nbsp; DIRECTION DE LA GARDE-CÔTES</td>
                    <td>Grade: &nbsp; <?php echo e($employee->grade); ?></td>
                </tr>
                <tr>
                    <td>N° Compte: &nbsp; <?php echo e($employee->account_number); ?></td>
                    <td></td>
                </tr>
            </table>
        </div>

        <?php
            $allowances = [];
            $deductions = [];
            $loans = [];
            $otherPayments = [];
        ?>

        <?php $__currentLoopData = $payslipDetail['earning']['otherPayment']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $otherPayment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $otherpay = json_decode($otherPayment->other_payment);
            ?>
            <?php $__currentLoopData = $otherpay; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php array_push($otherPayments, ['title' => $op->title, 'amount' => \Auth::user()->priceFormat($op->amount)]) ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php $__currentLoopData = $payslipDetail['deduction']['loan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $loan = json_decode($loan->loan);
            ?>
            <?php $__currentLoopData = $loan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emploanss): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php array_push($loans, ['title' => $emploanss->title, 'amount' => \Auth::user()->priceFormat($emploanss->amount)]) ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php $__currentLoopData = $payslipDetail['deduction']['deduction']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $deduction = json_decode($deduction->saturation_deduction);
            ?>
            <?php $__currentLoopData = $deduction; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $saturationdeduc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php array_push($deductions, ['title' => $saturationdeduc->title, 'amount' => \Auth::user()->priceFormat($saturationdeduc->amount)]) ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
        <?php $__currentLoopData = $payslipDetail['earning']['allowance']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allowance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $allowance = json_decode($allowance->allowance);
            ?>
            <?php $__currentLoopData = $allowance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $all): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php array_push($allowances, ['title' => $all->title, 'amount' => \Auth::user()->priceFormat($all->amount)]) ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php $__currentLoopData = $otherPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $otherPa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo e($otherPa['title']); ?>

        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="p-4 mb-4">
            <h3 class="mb-2">
                <span class="border p-1 border-2 border-black px-4 mr-8">TRAITEMENT DE LA SOLDE</span>
                <span>Solde Base: &nbsp; <?php echo e(\Auth::user()->priceFormat($employee->salary)); ?></span>
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
                                    <?php $__currentLoopData = $allowances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allowan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($allowan['title'] == 'Mission Special'): ?>
                                            <?php echo e($allowan['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Ind.S.Police:</td>
                                <td>
                                    <?php $__currentLoopData = $allowances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allowan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($allowan['title'] == 'Sujet Police'): ?>
                                            <?php echo e($allowan['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Mensuelle_RespPart:</td>
                                <td>
                                <?php $__currentLoopData = $allowances; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allowan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($allowan['title'] == 'Mensuelle RespPart'): ?>
                                            <?php echo e($allowan['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Pharmacie:</td>
                                <td>0</td>
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
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Ret logem'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>                                       
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Subv:</td>
                                <td>                                    
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Ret Sub'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> 
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Foyer:</td>
                                <td>                                    
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Ret foyer'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>                                    
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Collecte:</td>
                                <td>                                    
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'RET COLLECT'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>                                      
                                </td>
                            </tr>
                            <tr>
                                <td><mark>Ret Caisse Social:</mark></td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Ret Fond Habitat:</td>
                                <td>                                                                        
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'FONT HABITAT'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> 
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Collect Medecin:</td>
                                <td>
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Ret Medical'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> 
                                </td>
                            </tr>
                            <tr>
                                <td><mark>Ret Chebeleye:</mark></td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Ret Popote:</td>
                                <td>
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'RET POPOTE'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> 
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Waqf:</td>
                                <td>
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Ret Waqf'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Ret Al-Gamil:</td>
                                <td>
                                    <?php $__currentLoopData = $loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($loa['title'] == 'Algamil'): ?>
                                            <?php echo e($loa['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> 
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
                                    <?php $__currentLoopData = $otherPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $otpay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($otpay['title'] == 'All,eau'): ?>
                                            <?php echo e($otpay['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>PFranc:</td>
                                <td>
                                    <?php $__currentLoopData = $otherPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $otpay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($otpay['title'] == 'PFranc'): ?>
                                            <?php echo e($otpay['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'CNR'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                <td><?php echo e(\Auth::user()->priceFormat($payslip->net_payble)); ?></td>
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
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Abatt'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Mont Imp:</td>
                                <td>
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Mont Impôt'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Impôt:</td>
                                <td>
                                    <?php $__currentLoopData = $deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($deduct['title'] == 'Ret Impot'): ?>
                                            <?php echo e($deduct['amount']); ?>

                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>                                    
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?php echo e(asset('js/html2pdf.bundle.min.js')); ?>"></script>
<script>
    function saveAsPDF() {
        var element = document.getElementById('printableArea');
        var opt = {
            margin: 0.3,
            filename: '<?php echo e($employee->name); ?>',
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
</script><?php /**PATH /home/medeni/dev/hypercube/hrgo/resources/views/payslip/pdf.blade.php ENDPATH**/ ?>