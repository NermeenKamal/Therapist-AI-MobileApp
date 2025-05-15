<!--  inheritance from another file -->

<?php $__env->startSection('title','AddMeal'); ?>


<?php $__env->startSection('content'); ?>
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Add A Meal
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="<?php echo e(route('meals.store')); ?>" method="post">
                            <?php echo csrf_field(); ?> 
                                      
                            <div>
                                <input type="text" name="name" class="form-control" placeholder="Meal Name" />
                            </div>
                            <div>
                                <input type="text" name="description" class="form-control" placeholder="Description" />
                            </div>
                            <div>
                                <input type="number" name="price" min="1" class="form-control" placeholder="Price" />
                            </div>
                            <div>
                                <input type="file" name="photo" class="form-control" placeholder="photo" />
                            </div>
                            <div class="btn_box">
                                <button type="submit" href="<?php echo e(route('meals.index')); ?>">
                                    Add Meal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container ">
                        <div id="googleMap"> <img src="<?php echo e(asset('assets/images/f4.png')); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/meals/create.blade.php ENDPATH**/ ?>