<?php
    $chatgpt = Utility::getValByName('enable_chatgpt');
?>

<?php echo e(Form::model($contract, ['route' => ['contract.update', $contract->id], 'method' => 'PUT'])); ?>

<div class="modal-body">

    <?php if($chatgpt == 'on'): ?>
    <div class="text-end">
        <a href="#" class="btn btn-sm btn-primary" data-size="medium" data-ajax-popup-over="true"
            data-url="<?php echo e(route('generate', ['contract'])); ?>" data-bs-toggle="tooltip" data-bs-placement="top"
            title="<?php echo e(__('Generate')); ?>" data-title="<?php echo e(__('Generate Content With AI')); ?>">
            <i class="fas fa-robot"></i><?php echo e(__(' Generate With AI')); ?>

        </a>
    </div>
    <?php endif; ?>

    <div class="row">

        <div class="col-md-6 form-group">
            <?php echo e(Form::label('employee_name', __('Employee Name'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::select('employee_name', $employee, null, ['class' => 'form-control select2', 'required' => 'required'])); ?>

        </div>
        <div class="col-md-6 form-group">
            <?php echo e(Form::label('subject', __('Subject'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::text('subject', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>
        <div class="col-md-6 form-group">
            <?php echo e(Form::label('echelle', __('Echelle'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::text('echelle', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>
        <div class="col-md-6 form-group">
            <?php echo e(Form::label('indice', __('Indice'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::text('indice', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>
        <div class="col-md-6 form-group">
            <?php echo e(Form::label('grade', __('Grade'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::text('grade', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>
        <div class="col-md-6 form-group">
            <?php echo e(Form::label('value', __('Value'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::number('value', null, ['class' => 'form-control', 'required' => 'required', 'min' => '1'])); ?>

        </div>
        <div class="col-md-6 form-group">
            <?php echo e(Form::label('type', __('Type'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::select('type', $contractType, null, ['class' => 'form-control select2', 'required' => 'required'])); ?>

        </div>
        <div class="form-group col-md-6">
            <?php echo e(Form::label('start_date', __('Start Date'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::date('start_date', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>
        <div class="form-group col-md-6">
            <?php echo e(Form::label('end_date', __('End Date'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::date('end_date', null, ['class' => 'form-control', 'required' => 'required'])); ?>

        </div>
        <div class="col-md-12 form-group">
            <?php echo e(Form::label('description', __('Description'), ['class' => 'col-form-label'])); ?>

            <?php echo e(Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3'])); ?>

        </div>
        
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-light" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
    <button type="submit" class="btn  btn-primary"><?php echo e(__('Update')); ?></button>

</div>

<?php echo e(Form::close()); ?>

<?php /**PATH /home/medeni/dev/hrmgo/resources/views/contracts/edit.blade.php ENDPATH**/ ?>