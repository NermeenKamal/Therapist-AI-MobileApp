<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title><?php echo $__env->yieldContent('title',"CourseTool"); ?></title>
    <link rel="stylesheet"  href="<?php echo e(asset('css/style.css')); ?>">
</head>
<body>
<header>
    <ul>
        <li><a href="<?php echo e(url('/')); ?>">home</a></li>
        <li><a href="<?php echo e(url('courses')); ?>">courses</a></li>
        <li><a href="<?php echo e(url('students')); ?>">students</a></li>
    </ul>
</header>

<div class="container">
    <h3>content start</h3>
    <?php echo $__env->yieldContent('content'); ?>
</div>

<footer>
    <p> &copy; 2025 CourseTool</p>
</footer>
</body>
</html>
<?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views////layouts/app.blade.php ENDPATH**/ ?>