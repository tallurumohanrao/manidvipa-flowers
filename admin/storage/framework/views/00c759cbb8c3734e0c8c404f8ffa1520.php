<!DOCTYPE html>
<html lang="en">
<head><meta http-equiv="Content-Type" content="text/html; charset=gb18030">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Panel</title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('storage/website/'.config('SITE_FAVICON'))); ?>">
    <!-- Custom fonts for this template-->
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/vendor/fontawesome-free/css/all.min.css')); ?>" />
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <!-- Custom styles for this template-->
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/sb-admin-2.min.css')); ?>" />
    
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/notific.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/sweetalert2.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/select2.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/style.css')); ?>" />
    <link media="all" type="text/css" rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
    <?php echo $__env->yieldPushContent('styles'); ?>
    <style>
        .cke_notifications_area{
            display: none;
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php echo $__env->make('admin.includes.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                    <?php if(request('developer') == 'yes'): ?>
                        <?php if(File::exists(storage_path('framework/down'))==1): ?>
                        <li class="list-inline-item"><?php echo e(link_to_route('admin.up','Maintenance OFF', NULL, [ 'class' => 'nav-link' ])); ?></li>
                        <?php else: ?>
                        <li class="list-inline-item">
                            <a href="javascript:maintenance()" class="nav-link">Maintenance ON</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if(Route::has('admin.clear')): ?>
                        <li class="list-inline-item">
                            <a href="<?php echo e(route('admin.clear')); ?>" class="nav-link">Clear Cache</a>
                        </li>
                    <?php endif; ?>
                    </ul>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <?php if(auth()->guard()->check()): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo e(auth()->user()->name); ?></span>
                                <?php if(@auth()->user()->image && File::exists(public_path('storage/admins/'.@auth()->user()->image))): ?>
                                <?php echo e(Html::img(asset('storage/admins/'.auth()->user()->image), auth()->user()->name)->class('img-profile rounded-circle')); ?>

                                <?php endif; ?>
                            </a>
                            <?php endif; ?>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <?php if(Route::has('admin.account')): ?>
                                <a class="dropdown-item" href="<?php echo e(route('admin.account')); ?>">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('settings_view')): ?>
                                <a class="dropdown-item" href="<?php echo e(route('admin.settings.index')); ?>">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <?php endif; ?>
                                
                                <?php if(Route::has('admin.logout')): ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:$('#logout-form').submit();">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                                <?php echo e(html()->form('POST', '/admin/logout')->id('logout-form')->class('d-none')->open()); ?>

                                <?php echo e(html()->submit('Logout')); ?>

                                <?php echo e(html()->form()->close()); ?>

                                <?php endif; ?>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <?php echo $__env->yieldContent('content'); ?>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; <?php echo config('SITE_NAME').' '.now()->year; ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="javascript:$('#logout-form').submit();">Logout</a>
                </div>
            </div>
        </div>
    </div>

   <!-- Bootstrap core JavaScript-->
   <script src="<?php echo e(asset('assets/admin/vendor/jquery/jquery.min.js')); ?>"></script>
   <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.js"></script>
   <script src="<?php echo e(asset('assets/admin/vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
   <!-- Core plugin JavaScript-->
   <script src="<?php echo e(asset('assets/admin/vendor/jquery-easing/jquery.easing.min.js')); ?>"></script>
   
   <script src="<?php echo e(asset('assets/admin/js/validate.min.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/admin/js/select2.min.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/admin/js/notific.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/admin/js/sweetalert2.min.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/admin/js/custom.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/admin/js/sb-admin-2.min.js')); ?>"></script>
   <script src="https://cdn.ckeditor.com/4.12.1/standard/ckeditor.js"></script>

    <script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <?php echo $__env->yieldContent('script'); ?>
    <?php echo $__env->yieldPushContent('script'); ?>
    <script>

$( function() {
    $( ".datepicker" ).datepicker({
        minDate : 0,
        dateFormat: 'yy-mm-dd',
    });
});
    <?php if(Route::has('admin.ckeditor.upload')): ?>
	$(function(){
		$('.editor').each(function(e){
			CKEDITOR.replace( this.id, {
				filebrowserUploadUrl: "<?php echo e(route('admin.ckeditor.upload', ['_token' => csrf_token(),'type'=>'file' ])); ?>",
				filebrowserImageUploadUrl: "<?php echo e(route('admin.ckeditor.upload', ['_token' => csrf_token(),'type'=>'image' ])); ?>",
				filebrowserUploadMethod: 'form',
				allowedContent:true
			});
		});
	})
    <?php endif; ?>
	$(function(){
		<?php echo $__env->make('admin.includes.flash-message', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
	})
    <?php if(Route::has('admin.down')): ?>
	function maintenance(){
	    if(!confirm('Do you want to proceed site under maintenance?')){
	        return false;
        }else{
            $(location).attr('href','<?php echo e(route("admin.down")); ?>');
        }
	}
    <?php endif; ?>
	</script>
</body>
</html>
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/layouts/app.blade.php ENDPATH**/ ?>