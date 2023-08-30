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
    <?php $__currentLoopData = $loans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($loan[0]); ?></td>
            <td><?php echo e($loan[1]); ?></td>
            <td><?php echo e($loan[2]); ?></td>
            <td><?php echo e($loan[3]); ?></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php /**PATH /home/medeni/dev/hrgo/resources/views/loan/export.blade.php ENDPATH**/ ?>