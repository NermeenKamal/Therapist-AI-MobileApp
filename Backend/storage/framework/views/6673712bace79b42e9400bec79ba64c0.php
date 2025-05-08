<!--  inheritance from another file -->
<?php $__env->startSection('title','home'); ?>

<?php $__env->startSection('content'); ?>
    <p>this is my content</p>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('../layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/course/index.blade.php ENDPATH**/ ?>