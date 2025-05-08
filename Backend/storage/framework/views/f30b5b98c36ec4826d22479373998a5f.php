<?php $__env->startSection('title','DisplayChefs'); ?>
<?php $__env->startSection('chefactive'); ?>
    </li> <li class="nav-item active">
    <?php $__env->stopSection(); ?>
    <?php $__env->startSection('disactive'); ?>
</li><li class="nav-item">
    <?php $__env->stopSection(); ?>

    <?php $__env->startSection('content'); ?>
        <div style="display: flex; justify-content: center;">
            <div class="card" style="width: 18rem;">
                <img class="card-img-top" src="<?php echo e(asset($chef['photo'])); ?>" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title"><?php echo e($chef['name']); ?></h5>
                    <p class="card-text"><?php echo e($chef['description']); ?></p>
                    <p class="card-text"><?php echo e($chef['experience']); ?></p>
                </div>
            </div>
        </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/chefs/view.blade.php ENDPATH**/ ?>