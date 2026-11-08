@extends('front.layouts.app')
@section('content')
<div class="col-xl-12 col-lg-12 col-md-12 col-12 margin_top_15 padding-bottom-5-prcnt">
    <div class="fr_lr con-img">
        <div class="container-fluid ">
            <div class="col-lg-12">
                <div class="col-xl-12 col-lg-12 col-md-12 col-12 text-center clearfix" style="height: auto;padding: 20px 0px">
                    <h1 class="heading-1 google_marcellus_SC text_shadow_5 size-3-vw"><span> @yield('heading')</span></h1>
                </div>
                <div class="col-md-12 col-md-12 col-12 margin_bottom_10">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-12 bg_trans_white_80 padding-top-3-prcnt text-start line-h-24 margin_bottom_10 content_img">
                        @yield('inner')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
