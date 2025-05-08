<?php $__env->startSection('title','DisplayMeals'); ?>
<?php $__env->startSection('mealactive'); ?>
    </li> <li class="nav-item active">
    <?php $__env->stopSection(); ?>
    <?php $__env->startSection('disactive'); ?>
</li><li class="nav-item">
<?php $__env->stopSection(); ?>

    <?php $__env->startSection('content'); ?>
        <div style="display: flex; justify-content: center;">
    <div class="card" style="width: 18rem;">
        <img class="card-img-top" src="<?php echo e(asset($meals['photo'])); ?>" alt="Card image cap">
        <div class="card-body">
            <h5 class="card-title"><?php echo e($meals['name']); ?></h5>
            <p class="card-text"><?php echo e($meals['description']); ?></p>
            <p class="card-text"><?php echo e($meals['price']); ?></p>
        </div>
    </div>
        </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/meals/view.blade.php ENDPATH**/ ?>