<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h3>nrmn</h3>
     
    <?php echo e($name); ?>

<br>
<?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php echo e($item); ?>

    <br>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php for($i=0;$i<10;$i++): ?>
    <?php echo e($i); ?> <br>
<?php endfor; ?>

<?php
$arr = array("car",0,2.3,true);
foreach ($arr as $item){
    echo $item." ";
}
?>

</body>
</html>
<?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/test.blade.php ENDPATH**/ ?>