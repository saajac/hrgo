<table>
    <thead>
        <tr>
            <th>Noms</th>
            <th>Grade</th>
            <th>Indice</th>
            <th>SalaireBase</th>
            <th>Sujet Police</th>
            <th>Mensuelle RespPart</th>
            <th>Mission Special</th>
            <th>prime</th>
            <th>Ind medecin</th>
            <th>CNR</th>
            <th>Abatt *5%</th>
            <th>MontImp</th>
            <th>RET Impot</th>
            <th>Retmedical</th>
            <th>RET waqf</th>
            <th>Sai Arret</th>
            <th>FONT HABITAT</th>
            <th>Ret logem</th>
            <th>RET COLLECT</th>
            <th>Ret Sub</th>
            <th>Ret foyer</th>
            <th>RET POPOTE</th>
            <th>RET A,S</th>
            <th>Retalgamil</th>
            <th>All,eau</th>
            <th>Press, Fam</th>
            <th>Pm forfaitairePFranc</th>
            <th>PFranc</th>
            <th>NET A PAYE</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($employee['name']); ?></td>
            <td><?php echo e($employee['grade']); ?></td>
            <td><?php echo e($employee['indice']); ?></td>
            <td><?php echo e($employee['salary']); ?></td>
            <?php $__currentLoopData = $employee['allowance']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allowance): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <td><?php echo e($allowance->amount); ?></td>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <td><?php echo e($employee['cnr']); ?></td>
            <td>
                <?php echo e($employee['abatt']); ?>

            </td>
            <td>
                <?php echo e($employee['salary'] + $employee['allowance_total'] - $employee['cnr'] - $employee['abatt']); ?>

            </td>
            <td></td>
            <td><?php echo e($employee['retmedical']); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table><?php /**PATH /home/medeni/dev/hrgo/resources/views/payslip/export.blade.php ENDPATH**/ ?>