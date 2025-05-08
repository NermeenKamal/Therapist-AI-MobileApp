<?php $__env->startSection('title','Edit Chef'); ?>

<?php $__env->startSection('content'); ?>
    <section class="book_section layout_padding">
        <div class="container">
            <div class="heading_container">
                <h2>
                    Edit Chef Profile
                </h2>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form_container">
                        <form action="<?php echo e(route('chefs.update', $chef['id'])); ?>" method="post" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('put'); ?>

                            <div>
                                <input type="text" name="name" value="<?php echo e($chef['name']); ?>"
                                       class="form-control" placeholder="Chef Name" required>
                            </div>
                            <div>
                                <input type="number" name="experience" value="<?php echo e($chef['experience']); ?>"
                                       min="1" class="form-control" placeholder="<?php echo e($chef['experience']); ?>" required>
                            </div>
                            <div>
                                <textarea name="description" class="form-control"
                                          placeholder="Chef Description" rows="5" required><?php echo e($chef['description']); ?></textarea>
                            </div>
                            <div>
                                <input type="file" name="photo" class="form-control-file">
                            </div>
                            <div class="btn_box">
                                <button type="submit" class="btn-primary">
                                    Update Chef Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="map_container">
                        <div id="chef-photo-preview">
                            <img style="width: 80%; height: auto;"
                                 src="<?php echo e(asset($chef['photo'])); ?>"
                                 alt="<?php echo e($chef['name']); ?>'s Current Photo">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\Nermeen Kamal\OneDrive\Desktop\ITI PHP\myproject\resources\views/chefs/edit.blade.php ENDPATH**/ ?>