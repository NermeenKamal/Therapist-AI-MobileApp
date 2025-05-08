<!--  inheritance from another file -->

<?php $__env->startSection('title','AddChefs'); ?>


<?php $__env->startSection('content'); ?>
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Add A Chef
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="<?php echo e(route('chefs.store')); ?>" method="post">
                            <?php echo csrf_field(); ?> 
                            
                            <div>
                                <input type="text" name="name" class="form-control" placeholder="Chef Name" />
                            </div>
                            <div>
                                <input type="text" name="description" class="form-control" placeholder="Description" />
                            </div>
                            <div>
                                <input type="number" name="experience" min="1" class="form-control" placeholder="Years Of Experience" />
                            </div>
                            <div>
                                <input type="file" name="photo" class="form-control" placeholder="photo" />
                            </div>
                            <div class="btn_box">
                                <button type="submit" href="<?php echo e(route('chefs.index')); ?>">
                                    Add Chef
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container ">
                        <div id="googleMap"> <img src="<?php echo e(asset('assets/images/team-3.jpg')); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/chefs/create.blade.php ENDPATH**/ ?>