<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    @include('includes.seo')
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Place favicon.png in the root directory -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/website/'.config('SITE_FAVICON')) }}">
    <!-- Font Icons css -->
    <link rel="stylesheet" href="{{ asset('assets/css/font-icons.css') }}">
    <!-- plugins css -->
    <link rel="stylesheet" href="{{ asset('assets/css/plugins.css') }}">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <!-- Responsive css -->
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/sweetalert2.min.css') }}">
    <meta name="robots" content="noindex,nofollow">
</head>

<body>
<div class="body-wrapper">
    <!-- HEADER AREA START (header-3) -->
    <header class="ltn__header-3 section-bg-6">
        <!-- ltn__header-top-area start -->
        <div class="ltn__header-top-area">
            <div class="container">
                <div class="row">
                    <div class="col-md-7">
                        <div class="ltn__top-bar-menu">
                            <ul>
                                <li><a href="mailto:{{ config('SITE_EMAIL') }}"><i class="icon-mail"></i> {{ config('SITE_EMAIL') }}</a></li>
                                <li><a href="tel:{{ config('SITE_PHONE') }}"><i class="icon-call"></i>{{ config('SITE_PHONE') }}</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="top-bar-right text-right">
                            <div class="ltn__top-bar-menu">
                                <ul>
                                    <li>
                                        <!-- ltn__social-media -->
                                        <div class="ltn__social-media">
                                            <ul>
                                                @if(config('FACEBOOK_LINK'))
                                                <li><a target="_blank" href="{{ config('FACEBOOK_LINK') }}" title="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                                                @endif
                                                @if(config('INSTAGRAM_LINK'))
                                                <li><a target="_blank" href="{{ config('INSTAGRAM_LINK') }}" title="Instagram"><i class="fab fa-instagram"></i></a></li>
                                                @endif
                                                @if(config('YOUTUBE_LINK'))
                                                <li><a target="_blank" href="{{ config('YOUTUBE_LINK') }}" title="Youtube"><i class="fab fa-youtube"></i></a></li>
                                                @endif
                                                @if(config('PINTEREST_LINK'))
                                                <li><a target="_blank" href="{{ config('PINTEREST_LINK') }}" title="Pinterest"><i class="fab fa-pinterest"></i></a></li>
                                                @endif
                                                @if(config('TWITTER_LINK'))
                                                <li><a target="_blank" href="{{ config('TWITTER_LINK') }}" title="Twitter"><i class="fab fa-twitter"></i></a></li>
                                                @endif
                                                @if(config('LINKED_LINK'))
                                                <li><a target="_blank" href="{{ config('LINKED_LINK') }}" title="Linkedin"><i class="fab fa-linkedin"></i></a></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ltn__header-top-area end -->
        <!-- ltn__header-middle-area start -->

        <div class="ltn__header-middle-area ltn__header-sticky ltn__sticky-bg-white sticky-active-into-mobile--- plr--9---">
            <div class="container p-2">
                <div class="row">
                    <div class="col">
                        <div class="site-logo-wrap">
                            <div class="site-logo">
                                <a href="/">
                                    <img src="{{ asset('storage/website/'.config('SITE_LOGO')) }}" alt="{{ config('SITE_NAME') }}" />
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col header-menu-column menu-color-white---">
                        <div class="header-menu d-none d-xl-block">
                            <nav>
                                <div class="ltn__main-menu">
                                    <ul>
                                        <li>
                                            <a href="/">Home</a>
                                        </li>
                                        <li>
                                            <a href="/about-us">About</a>
                                        </li>
                                        <li class="menu-icon"><a href="/store">Shop</a>
                                            <ul class="navli mega-menu">
                                                @foreach($primarymenu as $pmenu)
                                                <li><a href="{{ route('categories',['slug'=>$pmenu->slug]) }}">{{ $pmenu->title }}</a></li>
                                                @endforeach
                                            </ul>
                                        </li>
                                        <li><a href="/gallery">Gallery</a></li>
                                        <li><a href="/contact-us">Contact</a></li>
                                        <li><a href="/posts">Blog</a></li>
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    </div>
                    <div class="ltn__header-options ltn__header-options-2">
                        <div class="ltn__drop-menu user-menu">
                            <ul>
                                <li>
                                    <a href="#"><i class="icon-user"></i></a>
                                    <ul>
                                        @auth
                                        <li><a href="/account">My Account</a></li>
                                        <li><a href="/wishlist">Wishlist</a></li>
                                        <li><a href="javascript:$('#logout-form').submit();">Logout</a></li>
                                        @else
                                        <li><a href="/login">Sign in</a></li>
                                        <li><a href="/register">Register</a></li>
                                        @endauth
                                    </ul>
                                </li>
                            </ul>
                        </div>
                        <!-- mini-cart -->
                        <div class="mini-cart-icon">
                            <a href="/cart">
                                <i class="icon-shopping-cart"></i>
                                <sup class="miniCart"></sup>
                            </a>
                        </div>
                        <!-- mini-cart -->
                        <!-- Mobile Menu Button -->
                        <div class="mobile-menu-toggle d-xl-none">
                            <a href="#ltn__utilize-mobile-menu" class="ltn__utilize-toggle">
                                <svg viewBox="0 0 800 600">
                                    <path d="M300,220 C300,220 520,220 540,220 C740,220 640,540 520,420 C440,340 300,200 300,200" id="top"></path>
                                    <path d="M300,320 L540,320" id="middle"></path>
                                    <path d="M300,210 C300,210 520,210 540,210 C740,210 640,530 520,410 C440,330 300,190 300,190" id="bottom" transform="translate(480, 320) scale(1, -1) translate(-480, -318) "></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ltn__header-middle-area end -->
    </header>
    <!-- HEADER AREA END -->

    <!-- Utilize Mobile Menu Start -->
    <div id="ltn__utilize-mobile-menu" class="ltn__utilize ltn__utilize-mobile-menu">
        <div class="ltn__utilize-menu-inner ltn__scrollbar">
            <div class="ltn__utilize-menu-head">
                <div class="site-logo">
                    <a href="/">
                        <img src="{{ asset('storage/website/'.config('SITE_LOGO')) }}" alt="{{ config('SITE_NAME') }}" />
                    </a>
                </div>
                <button class="ltn__utilize-close">×</button>
            </div>
            <div class="ltn__utilize-menu">
                <ul>
                    <li>
                        <a href="/">Home</a>
                    </li>
                    <li>
                        <a href="/about-us">About</a>
                    </li>
                    <li><a href="/store">Shop</a>
                        <ul class="sub-menu">
                            @foreach($primarymenu as $pmenu)
                            <li><a href="{{ route('categories',['slug'=>$pmenu->slug]) }}">{{ $pmenu->title }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                    <li><a href="/gallery">Gallery</a></li>
                    <li><a href="/contact-us">Contact</a></li>
                    <li><a href="/posts">Blog</a></li>
                </ul>
            </div>
            <div class="ltn__utilize-buttons ltn__utilize-buttons-2">
                <ul>
                    <li>
                        <a href="/account" title="My Account">
                            <span class="utilize-btn-icon">
                                <i class="far fa-user"></i>
                            </span>
                            My Account
                        </a>
                    </li>
                    <li>
                        <a href="/wishlist" title="Wishlist">
                            <span class="utilize-btn-icon">
                                <i class="far fa-heart"></i>
                            </span>
                            Wishlist
                        </a>
                    </li>
                    <li>
                        <a href="/cart" title="Shoping Cart">
                            <span class="utilize-btn-icon">
                                <i class="fas fa-shopping-cart"></i>
                                <sup class="miniCart"></sup>
                            </span>
                            Shoping Cart
                        </a>
                    </li>
                </ul>
            </div>
            <div class="ltn__social-media-2">
                <ul>
                    @if(config('FACEBOOK_LINK'))
                    <li><a href="{{ config('FACEBOOK_LINK') }}" title="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                    @endif
                    @if(config('INSTAGRAM_LINK'))
                    <li><a href="{{ config('INSTAGRAM_LINK') }}" title="Instagram"><i class="fab fa-instagram"></i></a></li>
                    @endif
                    @if(config('PINTEREST_LINK'))
                    <li><a href="{{ config('PINTEREST_LINK') }}" title="Pinterest"><i class="fab fa-pinterest"></i></a></li>
                    @endif
                    @if(config('TWITTER_LINK'))
                    <li><a href="{{ config('TWITTER_LINK') }}" title="Twitter"><i class="fab fa-twitter"></i></a></li>
                    @endif
                    @if(config('LINKED_LINK'))
                    <li><a href="{{ config('LINKED_LINK') }}" title="Linkedin"><i class="fab fa-linkedin"></i></a></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    <!-- Utilize Mobile Menu End -->

@yield('content')

<!-- FOOTER AREA START -->
<footer class="ltn__footer-area  ">
    <div class="footer-top-area  section-bg-1 plr--5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-3 col-md-6 col-sm-6 col-12">
                    <div class="footer-widget footer-about-widget">
                        <div class="footer-logo mb-10">
                            <div class="site-logo">
                                <a href="/">
                                <img src="{{ asset('storage/website/'.config('SITE_LOGO')) }}" alt="{{ config('SITE_NAME') }}" />
                                </a>
                            </div>
                        </div>
                        <p>Buy Original Tanjore Paintings in Hyderabad</p>
                        <div class="footer-address">
                            <ul>
                                <li>
                                    <div class="footer-address-icon">
                                        <i class="icon-call"></i>
                                    </div>
                                    <div class="footer-address-info">
                                        <p><a href="tel:{{ config('SITE_PHONE') }}">{{ config('SITE_PHONE') }}</a></p>
                                    </div>
                                </li>
                                <li>
                                    <div class="footer-address-icon">
                                        <i class="icon-mail"></i>
                                    </div>
                                    <div class="footer-address-info">
                                        <p><a href="mailto:{{ config('SITE_EMAIL') }}">{{ config('SITE_EMAIL') }}</a></p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="ltn__social-media mt-20">
                            <ul>
                                @if(config('FACEBOOK_LINK'))
                                <li><a target="_blank" href="{{ config('FACEBOOK_LINK') }}" title="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                                @endif
                                @if(config('INSTAGRAM_LINK'))
                                <li><a target="_blank" href="{{ config('INSTAGRAM_LINK') }}" title="Instagram"><i class="fab fa-instagram"></i></a></li>
                                @endif
                                @if(config('YOUTUBE_LINK'))
                                <li><a target="_blank" href="{{ config('YOUTUBE_LINK') }}" title="Youtube"><i class="fab fa-youtube"></i></a></li>
                                @endif
                                @if(config('PINTEREST_LINK'))
                                <li><a target="_blank" href="{{ config('PINTEREST_LINK') }}" title="Pinterest"><i class="fab fa-pinterest"></i></a></li>
                                @endif
                                @if(config('TWITTER_LINK'))
                                <li><a target="_blank" href="{{ config('TWITTER_LINK') }}" title="Twitter"><i class="fab fa-twitter"></i></a></li>
                                @endif
                                @if(config('LINKED_LINK'))
                                <li><a target="_blank" href="{{ config('LINKED_LINK') }}" title="Linkedin"><i class="fab fa-linkedin"></i></a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 col-sm-6 col-12">
                    @if($block1 = block(1))
                    {!! $block1->description !!}
                    @endif
                </div>
                <div class="col-xl-2 col-md-6 col-sm-6 col-12">
                    <div class="footer-widget footer-menu-widget clearfix">
                        <h4 class="footer-title">Quick Links</h4>
                        <div class="footer-menu">
                            <ul>
                                <li><a href="/">Home</a></li>
                                <li><a href="/about-us">About Us</a></li>
                                <li><a href="/gallery">Gallery</a></li>
                                <li><a href="/posts">Blog</a></li>
                                <li><a href="/contact-us">Contact us</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                 {{--<div class="col-xl-2 col-md-6 col-sm-12 col-12">
                    <div class="footer-widget footer-newsletter-widget">
                        
                        <h5 class="mt-30">We Accept</h5>
                        <img src="img/icons/payment-4.png" alt="Payment Image">
                    </div>
                </div> --}}

                <div class="col-xl-3 col-md-6 col-sm-6 col-12">
                    <div class="footer-widget footer-menu-widget clearfix">
                        <p>Buy Best Tanjore Paintings in Hyderabad If You are Looking to Buy Best and Original Tanjore Paintings in Hyderabad,We Sell only genuine paintings along with 35 Years of service  Warranty and Certification</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="ltn__copyright-area ltn__copyright-2 section-bg-1 border-top  ltn__border-top-2--- plr--5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 col-12">
                    <div class="ltn__copyright-design clearfix">
                        <p>All Rights Reserved &copy;<span class="current-year"></span>. Developed by <a target="_blank" href="https://www.vegawebsolutions.com">Vega Web Solutions</a></p>
                    </div>
                </div>
                <div class="col-md-6 col-12 align-self-center">
                    <div class="ltn__copyright-menu text-right">
                        <ul>
                            <li><a href="/terms-conditions">Terms & Conditions</a></li>
                            <li><a href="/privacy-policy">Privacy & Policy</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- FOOTER AREA END -->
</div>
<!-- Body main wrapper end -->
{{ html()->form('POST', '/logout')->id('logout-form')->class('d-none')->open() }}
{{ html()->submit('Logout') }}
{{ html()->form()->close() }}
<!-- All JS Plugins -->
<script src="{{ asset('assets/js/plugins.js') }}"></script>
<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/admin/js/notific.js') }}"></script>
<script src="{{ asset('assets/admin/js/sweetalert2.min.js') }}"></script>
<script>
    var baseUrl = '{{ url('') }}';
    var fullUrl = '{{ url()->full() }}';
    $(function(){
        @include('admin.partials.flash-message')
        $('.miniCart').load(baseUrl+'/cart-mini', function() {

        });
    })
</script>
@stack('script')
</body>
</html>
