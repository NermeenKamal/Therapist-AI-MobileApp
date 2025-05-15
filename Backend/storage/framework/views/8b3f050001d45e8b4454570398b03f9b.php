<?php $__env->startSection('title','DisplayChefs'); ?>
<?php $__env->startSection('chefactive'); ?>
    </li> <li class="nav-item active">
<?php $__env->stopSection(); ?>

    <?php $__env->startSection('disactive'); ?>
</li><li class="nav-item">
    <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section class="food_section layout_padding-bottom" id="chefs">
        <div class="container">
            <div class="heading_container heading_center">
                <h2>
                    Our Chefs
                </h2>
            </div>
            <div class="filters-content">
                <div class="row grid">
                    <div class="col-sm-6 col-lg-4 all pizza">
                        <div class="box">
                            <div>
                                <div class="img-box">
                                    <img class="team" src="<?php echo e(asset('assets/images/team-1.jpg')); ?>" alt="">
                                </div>
                                <div class="detail-box">
                                    <h5>
                                        Ashraf Khaled
                                    </h5>
                                    <p>
                                        Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque
                                    </p>
                                    <div class="options">
                                        <h6>
                                            4 Y Of E
                                        </h6>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4 all burger">

                        <div class="box">
                            <div>
                                <div class="img-box">
                                    <img class="team" src="<?php echo e(asset('assets/images/team-2.jpg')); ?>" alt="">
                                </div>
                                <div class="detail-box">
                                    <h5>
                                        Osama Mohamed
                                    </h5>
                                    <p>
                                        Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque
                                    </p>
                                    <div class="options">
                                        <h6>
                                            5 Y Of E
                                        </h6>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4 all pasta">
                        <div class="box">
                            <div>
                                <div class="img-box">
                                    <img class="team" src="<?php echo e(asset('assets/images/team-3.jpg')); ?>" alt="">
                                </div>
                                <div class="detail-box">
                                    <h5>
                                        Ahmed samy
                                    </h5>
                                    <p>
                                        Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque
                                    </p>
                                    <div class="options">
                                        <h6>
                                            4 Y Of E
                                        </h6>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4 all fries">
                        <div class="box">
                            <div>
                                <div class="img-box">
                                    <img class="team" src="<?php echo e(asset('assets/images/team-4.jpg')); ?>" alt="">
                                </div>
                                <div class="detail-box">
                                    <h5>
                                        Ali Tarek
                                    </h5>
                                    <p>
                                        Veniam debitis quaerat officiis quasi cupiditate quo, quisquam velit, magnam voluptatem repellendus sed eaque
                                    </p>
                                    <div class="options">
                                        <h6>
                                            6 Y Of E
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="CRUD">
                <a href="<?php echo e(url('app/addchef')); ?>">
                    <i class="fa fa-plus-circle" aria-hidden="false"></i>
                    <h3>Add</h3>
                </a>
                <a href="<?php echo e(url('app/deletechef')); ?>">
                    <i class="fa fa-minus-circle" aria-hidden="false"></i>
                    <h3>Delete</h3>
                </a>
                <a href="<?php echo e(url('app/displaychef')); ?>">
                    <i class="fa  fa-eye" aria-hidden="false"></i>
                    <h3>View</h3>
                </a>
                <a href="<?php echo e(url('app/updatechef')); ?>">
                    <i class="fa  fa-pencil-square-o" aria-hidden="false"></i>
                    <h3>Update</h3>
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
