<?php $__env->startSection('title','DisplayMeals'); ?>
<?php $__env->startSection('mealactive'); ?>
    </li> <li class="nav-item active">
<?php $__env->stopSection(); ?>
<?php $__env->startSection('disactive'); ?>
</li><li class="nav-item">
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <section class="food_section layout_padding-bottom">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>
                    Our Menu
                </h2>
            </div>
            <ul class="filters_menu">
                <li class="active" data-filter="*">All</li>
                <li data-filter=".burger">Burger</li>
                <li data-filter=".pizza">Pizza</li>
                <li data-filter=".pasta">Pasta</li>
                <li data-filter=".fries">Fries</li>
            </ul>
            <div class="filters-content">
                <div class="row grid">
            <?php $__currentLoopData = $meals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-sm-6 col-lg-4 all burger">
                        <div class="box">
                            <div>
                                <div class="img-box">
                                    <img src="<?php echo e($item['photo']); ?>">
                                </div>
                                <div class="detail-box">
                                    <h5>
                                        <?php echo e($item['name']); ?>

                                    </h5>
                                    <p>
                                        <?php echo e($item['description']); ?>

                                    </p>
                                    <div class="options">
                                        <h6>
                                            <?php echo e($item['price']); ?>

                                        </h6>
                                        <div class="crud">
                                            <a href="/displaymeal/<?php echo e($item['id']); ?>">
                                                <i class="fa  fa-eye" aria-hidden="false"></i>
                                                <h3>View</h3>
                                            </a>
                                            <a href="<?php echo e(url('app/updatemeal')); ?>">
                                                <i class="fa  fa-pencil-square-o" aria-hidden="false"></i>
                                                <h3>Update</h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="CRUD">
                <a href="<?php echo e(url('app/addmeal')); ?>">
                    <i class="fa fa-plus-circle" aria-hidden="false"></i>
                    <h3>Add</h3>
                </a>
                <a href="<?php echo e(url('app/deletemeal')); ?>">
                    <i class="fa fa-minus-circle" aria-hidden="false"></i>
                    <h3>Delete</h3>
                </a>
            </div>
            <div class="btn-box">
                <a href="">
                    View More
                </a>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

















<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/meals/index.blade.php ENDPATH**/ ?>
