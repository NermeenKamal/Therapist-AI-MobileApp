<?php $__env->startSection('title','DisplayChefs'); ?>
<?php $__env->startSection('chefactive'); ?>
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
                        Our Chefs
                    </h2>
                </div>
                <div class="filters-content">
                    <div class="row grid">
                        <?php $__currentLoopData = $chefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-sm-6 col-lg-4 all">
                                <div class="box">
                                    <div>
                                        <div class="img-box">
                                            <img class="team" src="<?php echo e($chef['photo']); ?>" alt="<?php echo e($chef['name']); ?>">
                                        </div>
                                        <div class="detail-box">
                                            <h5>
                                                <?php echo e($chef['name']); ?>

                                            </h5>
                                            <p>
                                                <?php echo e($chef['description']); ?>

                                            </p>
                                            <div class="options">
                                                <h6>
                                                    <?php echo e($chef['experience']); ?> Years Experience
                                                </h6>
                                                <div class="crud">
                                                    <a href="/chefs/<?php echo e($chef['id']); ?>">
                                                        <i class="fa fa-eye" aria-hidden="false"></i>
                                                        <h3>View</h3>
                                                    </a>
                                                    <a href="<?php echo e(route('chefs.edit', $chef['id'])); ?>">
                                                        <i class="fa fa-pencil-square-o" aria-hidden="false"></i>
                                                        <h3>Update</h3>
                                                    </a>
                                                    <a href="<?php echo e(route('chefs.index')); ?>">
                                                        <i class="fa fa-minus-circle" aria-hidden="false"></i>
                                                        <h3>Delete</h3>
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
                    <a href="<?php echo e(route('chefs.create')); ?>">
                        <i class="fa fa-plus-circle" aria-hidden="false"></i>
                        <h3>Add Chef</h3>
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

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/chefs/index.blade.php ENDPATH**/ ?>