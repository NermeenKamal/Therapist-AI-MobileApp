<!--  inheritance from another file -->

<?php $__env->startSection('title','Edit'); ?>


<?php $__env->startSection('content'); ?>
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Edit meal
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="<?php echo e(route('meals.update',$meals['id'])); ?>" method="post">   

                            <?php echo csrf_field(); ?> 
                            
                            <?php echo method_field('put'); ?>  

                            <div>
                                <input type="text" name="name" value="<?php echo e($meals['name']); ?>" class="form-control" placeholder="Meal Name" />
                            </div>
                            <div>
                                <input type="text" name="description" value="<?php echo e($meals['description']); ?>"  class="form-control" placeholder="Description" />
                            </div>
                            <div>
                                <input type="number" name="price" value="<?php echo e($meals['price']); ?>"  min="1" class="form-control" placeholder="<?php echo e($meals['price']); ?>" />
                            </div>
                            <div>
                                <input type="file" name="photo" value="<?php echo e($meals['photo']); ?>"  class="form-control" placeholder="<?php echo e($meals['photo']); ?>"  />
                            </div>
                            <div class="btn_box">
                                <button type="submit" href="<?php echo e(route('meals.index')); ?>">
                                    Edit meal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container ">
                        <div id="googleMap"> <img style="width: 50%; height: 75%;" src="<?php echo e(asset($meals['photo'])); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/meals/edit.blade.php ENDPATH**/ ?>