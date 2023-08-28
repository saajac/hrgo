<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Matricule</th>
        <th>Option</th>
        <th>Amount</th>
    </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $all_deductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($deduction[0]); ?></td>
            <td><?php echo e($deduction[1]); ?></td>
            <td><?php echo e($deduction[2]); ?></td>
            <td><?php echo e($deduction[3]); ?></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH C:\xampp\htdocs\hrmgo\resources\views/saturationdeduction/export.blade.php ENDPATH**/ ?>