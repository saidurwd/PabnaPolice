<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="keywords" content="@yield('meta_keywords', setting('meta_keywords'))">
        <meta name="description" content="@yield('meta_description', setting('meta_description'))">
        <title>@yield('title') - {{ setting('site_name') }}</title>
        <link rel="canonical" href="{{ url()->current() }}">
        <link rel="icon" type="image/png" href="{{ asset('assets/images/police-logo-circular.png') }}">
        <meta property="og:site_name" content="{{ setting('site_name') }}">
        <meta property="og:type" content="@yield('og_type', 'website')">
        <meta property="og:title" content="@yield('title') - {{ setting('site_name') }}">
        <meta property="og:description" content="@yield('meta_description', setting('meta_description'))">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="@yield('og_image', asset('assets/images/police-logo-circular.png'))">
        <meta name="twitter:card" content="summary">
        @stack('head')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <!--CSS-->
        <link rel="stylesheet" href="{{ theme_asset('bootstrap-3.3.7-dist/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ theme_asset('slick-master/slick/slick.css') }}">
        <link rel="stylesheet" href="{{ theme_asset('slick-master/slick/slick-theme.css') }}">
        <link rel="stylesheet" href="{{ theme_asset('fonts/font-awesome-4.7.0/css/font-awesome.css') }}">
        <link rel="stylesheet" href="{{ theme_asset('css/carouselTicker.css') }}">
        <link rel="stylesheet" href="{{ theme_asset('css/lightbox.css') }}">
        <link rel="stylesheet" href="{{ theme_asset('css/styles.css') }}">
        <link rel="stylesheet" href="{{ theme_asset('css/custom-styles.css') }}">
        <!--Scripts-->
        <script src="{{ theme_asset('js/jquery-3.2.0.min.js') }}"></script>
        <script src="{{ theme_asset('bootstrap-3.3.7-dist/js/bootstrap.min.js') }}"></script>
        <script src="{{ theme_asset('slick-master/slick/slick.js') }}"></script>
        <script src="{{ theme_asset('js/jquery.nav.js') }}"></script>
        <script src="{{ theme_asset('js/lightbox.js') }}"></script>
        <script src="{{ theme_asset('js/jquery.carouselTicker.min.js') }}"></script>
        <script src="{{ theme_asset('js/custom.js') }}"></script>
        <link href="https://fonts.googleapis.com/css?family=Courgette|Anton|Playball|Marcellus+SC|Great+Vibes|Pragati+Narrow|Pinyon+Script" rel="stylesheet">
        <style>
            .live_iframe>iframe{
                width: 100%!important;
                height: auto!important;
            }
        </style>
    </head>
    <body class="relative">

        <x-front.side-panels :links="$links" />

        <div class="container-fluid  bg_gray_white_gray border_bottom_5 common-pages">
            <div class="row">
                <!--- logo bar --->
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 logo_bar lgbar bg_grad_5" id="topends">
                    <div class="fr_lr">
                        <div class="container-fluid">
                            <div class="col-md-12">
                                <div class="col-md-12 col-sm-12 col-xs-12 text-left">
                                    <img src="{{ $headerBanner ? $headerBanner->imageUrl() : asset('uploads/banner/default.jpg') }}" alt="{{ $headerBanner?->title }}" title="{{ $headerBanner?->title }}" class="img-responsive">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--Back to top-->
                <div class="fixed toparrow bottom-0 z-index-1600 padding-10-10 right-0 scroll bg_trans_black_30 cursor-pointer" id="backtotop">
                    <i class="fa fa-angle-double-up white size-20"></i>
                </div>
                <x-front.nav :nav="$nav" :nav-gallery="$navGallery" :nav-document="$navDocument" />
            </div>
        </div>

        @yield('content')

        <x-front.footer :links="$links" />

        <script src="{{ theme_asset('js/quick-contact.js') }}"></script>
    </body>
</html>
