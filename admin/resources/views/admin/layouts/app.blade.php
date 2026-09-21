<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Panel</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteFavicon = config('SITE_FAVICON');
        $siteFaviconPath = $siteFavicon ? public_path('storage/website/'.$siteFavicon) : null;
        $faviconUrl = ($siteFaviconPath && file_exists($siteFaviconPath)) ? asset('storage/website/'.$siteFavicon) : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <!-- Custom fonts for this template-->
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/fontawesome-free/css/all.min.css') }}" />
    <!-- Custom styles for this template-->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/sb-admin-2.min.css') }}" />
    {{--<link rel="stylesheet" href="{{ asset('assets/admin/css/jquery.datetimepicker.min.css') }}" />--}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/notific.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/sweetalert2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/rich-editor.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/admin-shell.css') }}?v={{ @filemtime(public_path('assets/admin/css/admin-shell.css')) }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/admin-tables.css') }}?v={{ @filemtime(public_path('assets/admin/css/admin-tables.css')) }}" />
    @if(request()->is('admin/categories*'))
    <link rel="stylesheet" href="{{ asset('assets/admin/css/categories.css') }}?v={{ @filemtime(public_path('assets/admin/css/categories.css')) }}" />
    @endif
    @if(request()->routeIs('admin.bookings.create', 'admin.bookings.edit'))
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
    @endif
    @stack('styles')
    <style>
        .cke_notifications_area{
            display: none;
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        @include('admin.includes.sidebar')
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
                    @can('settings_edit')
                    @if(request('developer') == 'yes')
                        @if(File::exists(storage_path('framework/down'))==1)
                        <li class="list-inline-item">
                            <form method="POST" action="{{ route('admin.up') }}">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link">Maintenance OFF</button>
                            </form>
                        </li>
                        @else
                        <li class="list-inline-item">
                            <button type="button" onclick="maintenance()" class="btn btn-link nav-link">Maintenance ON</button>
                            {{-- link_to_route('admin.down','Maintenance ON', NULL, [ 'class' => 'nav-link', 'onclick' => 'javascript:maintenance()' ]) --}}</li>
                        @endif
                    @endif
                    @endcan
                    @can('settings_edit')
                    @if(Route::has('admin.clear'))
                        <li class="list-inline-item">
                            <form method="POST" action="{{ route('admin.clear') }}">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link">Clear Cache</button>
                            </form>
                        </li>
                    @endif
                    @endcan
                    </ul>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            @auth('admin')
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ auth('admin')->user()->name }}</span>
                                @if(auth('admin')->user()->image && File::exists(public_path('storage/admins/'.auth('admin')->user()->image)))
                                {{ Html::img(asset('storage/admins/'.auth('admin')->user()->image), auth('admin')->user()->name)->class('img-profile rounded-circle') }}
                                @endif
                            </a>
                            @endauth
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                @if(Route::has('admin.account'))
                                <a class="dropdown-item" href="{{ route('admin.account') }}">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                @endif
                                @can('settings_view')
                                <a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                @endcan
                                {{--<a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Activity Log
                                </a>--}}
                                @if(Route::has('admin.logout'))
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:$('#logout-form').submit();">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                                {{ html()->form('POST', '/admin/logout')->id('logout-form')->class('d-none')->open() }}
                                {{ html()->submit('Logout') }}
                                {{ html()->form()->close() }}
                                @endif
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                @include('admin.includes.flash-message')

                <!-- Begin Page Content -->
                @yield('content')
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; {!! config('SITE_NAME').' '.now()->year !!}</span>
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
   <script src="{{ asset('assets/admin/vendor/jquery/jquery.min.js') }}"></script>
   @if(request()->routeIs('admin.bookings.create', 'admin.bookings.edit'))
   <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.js"></script>
   @endif
   <script src="{{ asset('assets/admin/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
   <!-- Core plugin JavaScript-->
   <script src="{{ asset('assets/admin/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
   {{--<script src="{{ asset('assets/admin/js/jquery.datetimepicker.full.min.js') }}"></script>--}}
   <script src="{{ asset('assets/admin/js/validate.min.js') }}"></script>
   <script src="{{ asset('assets/admin/js/select2.min.js') }}"></script>
   <script src="{{ asset('assets/admin/js/notific.js') }}"></script>
   <script src="{{ asset('assets/admin/js/sweetalert2.min.js') }}"></script>
   <script src="{{ asset('assets/admin/js/custom.js') }}?v={{ @filemtime(public_path('assets/admin/js/custom.js')) }}"></script>
   <script src="{{ asset('assets/admin/js/admin-tables.js') }}?v={{ @filemtime(public_path('assets/admin/js/admin-tables.js')) }}"></script>
   <script src="{{ asset('assets/admin/js/sb-admin-2.min.js') }}"></script>
   <script>
       window.AdminRichEditorConfig = {
           uploadUrl: @json(Route::has('admin.ckeditor.upload') ? route('admin.ckeditor.upload') : null),
           csrfToken: @json(csrf_token())
       };
   </script>
   <script src="{{ asset('assets/admin/js/rich-editor.js') }}"></script>
    @yield('script')
    @stack('script')
    @can('settings_edit')
    @if(Route::has('admin.down'))
	<form method="POST" action="{{ route('admin.down') }}" id="maintenance-form" class="d-none">
        @csrf
    </form>
    @endif
    @endcan
    <script>

$(function() {
    if ($.fn.datepicker && $(".datepicker").length) {
        $(".datepicker").datepicker({
            minDate : 0,
            dateFormat: 'yy-mm-dd',
        });
    }
});
    $(function() {
        if (window.AdminRichEditor && typeof window.AdminRichEditor.initAll === 'function') {
            window.AdminRichEditor.initAll('.editor');
        }
    });
	$(function(){
		if (window.Message && typeof window.Message.init === 'function') {
			window.Message.init({life: 5000, timeOut: 250});
		}
	})
    @can('settings_edit')
    @if(Route::has('admin.down'))
	function maintenance(){
	    if(!confirm('Do you want to proceed site under maintenance?')){
	        return false;
        }else{
            document.getElementById('maintenance-form').submit();
        }
    }
    @endif
    @endcan
	</script>
</body>
</html>
