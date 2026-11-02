@extends('front.layouts.app')
@section('title', 'Home')
@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'GovernmentOrganization',
    'name' => setting('site_name'),
    'url' => url('/'),
    'logo' => asset('assets/images/police-logo-circular.png'),
    'email' => setting('admin_email'),
    'sameAs' => array_values(array_filter([setting('facebook_url'), setting('youtube_url'), setting('twitter_url')])),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush
@section('content')
<!---slider section --->
<div class="col-md-12 bg_white">
    <div class="fr_lr">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 banner">
            <div class="container-fluid ">
                <div class="col-lg-3 col-md-3">
                    <!--IGP Image and Message Link-->
                    <div class="col-md-12 bg_blue_site white box-shadow-6-1 text-center padding-10-10 relative" style="height: 305px; overflow: hidden;">
                        @if ($igp) {!! $igp->pictureHtml('igp_img') !!} @endif
                        <h2 class="margin_top_10">
                            <span class="block size-16">{{ $igp?->title }}</span>
                        </h2>
                        <div class="absolute left-0 bottom-0 margin_bottom_10 z-index-100 text-center width_full google_pragati_narrow">
                            <a href="{{ route('front.content.view', setting('home_igp_content')) }}" class="scroll btn button_4_1 size-16">Read more <i class="fa fa-angle-double-right"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9 col-md-9 col-sm-12 col-xs-12 banner_main no-padding-left hide_in_700">
                    <div class="bg_white banner_main_child box-shadow-6">
                        <div class="slider policeSlider" style="background: url('{{ asset('uploads/banner/a.jpg') }}') center center no-repeat;">
                            @foreach ($slider as $b)
                                <div class="image relative slider-box">
                                    <img src="{{ $b->imageUrl() }}" alt="{{ $b->title }}" class="img-responsive" title="{{ $b->title }}">
                                    <div class="absolute bottom-0 width_full white bg_trans_black_60 padding-6-6">{{ $b->title }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row second_look margin_bottom_1 bg_img_9 no-display" id="rrr">
    &nbsp;
</div>
<!--===== Recent Activities =====-->
<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 bg_gray_gradient">
    <div class="fr_lr">
        <div class="container-fluid">
            <div class="col-md-12">
                <div class="col-md-12 padding-bottom-5-prcnt ">
                    <div class="row">
                        <!--[Left] Widgets / Linked Buttons============================-->
                        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 text-left text-left">
                            <div class="col-md-12 margin_bottom_10">
                                <div class=" row box-shadow-6-1 relative z-index-10" style="height: 100%">
                                    <ul class="no-margin no-padding list-2 google_pragati_narrow">
                                        @foreach ($links->get('quick_left', []) as $l)
                                            <li class="bg_red_site text-left"><a href="{{ $l->href() }}" class="lnk-3-2">@if ($l->image)<img src="{{ $l->imageUrl() }}" class="bg_blue_site_1 icon_img_1" alt=""> @endif{{ $l->title }}</a> </li>
                                        @endforeach
                                    </ul>
                                    <ul class="no-margin no-padding list-2 google_pragati_narrow col-sm-12 col-xs-12 hidden-lg hidden-md">
                                        @foreach ($links->get('quick_right', []) as $l)
                                            <li class="bg_red_site text-right"><a href="{{ $l->href() }}" class="lnk-3-1 size-14">{{ $l->title }}@if ($l->image)<img src="{{ $l->imageUrl() }}" class="bg_blue_site_1 icon_img_2" alt="">@endif</a> </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="row box-shadow-6-1 relative z-index-10 margin_top_10 bg_red_white_2 red_site text-right google_pragati_narrow" style="height: 100%; padding:20px 10px 15px 0;">
                                    <h1 class="size-25 text-center">{{ setting('control_room_title') }}  </h1>
                                    <div class="size-20">
                                        <ul class="no-list text-justify">
                                            @foreach (preg_split('/\r\n|\r|\n/', trim((string) setting('control_room_numbers'))) as $line)
                                                @if (trim($line) !== '')<li><i class="fa fa-phone-square"></i> : {{ trim($line) }}</li>@endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!--Widget-1=================-->
                            <x-front.widget color="police-blue" shadow="box-shadow-6-1" title="Police Web Links" :links="$links->get('web_links', [])" />
                        </div>

                        <!--Recent Activities Contents================================-->
                        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 padding-bottom-2-prcnt text-left" style="background: url('{{ asset('assets/images/floral-2-5-1.png') }}') left top no-repeat,url('{{ asset('assets/images/floral-3-5-1.png') }}') right bottom no-repeat; background-color: #f0f0f0;">
                            <h1 class="heading-1 padding-3-0-prcnt blue_site text_shadow_1 google_marcellus_SC size-20">
                                &nbsp; {{ $recentCategory?->title }} &nbsp;
                            </h1>
                            <div class="row">
                                @foreach ($recent as $c)
                                    <div class="col-md-6 margin_top_10 margin_bottom_10">
                                        <div class="col-md-12 bg_white padding-bottom-10 box9 box-shadow-5 google_pragati_narrow padding_p">
                                            <h1 class="size-16 margin_bottom_10">
                                                <a href="{{ route('front.content.view', $c->id) }}">{{ str_chars_limit($c->title, 30) }}</a>
                                            </h1>
                                            <div style="background: url('{{ asset('uploads/content/'.$c->image) }}') center center; background-size: contain;background-repeat: no-repeat;height: 100px; "></div>
                                            {!! str_words_limit($c->details, 30) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="text-center padding-bottom-10">
                                <a href="{{ route('front.content.index', setting('home_recent_category')) }}" class="btn button_2 margin_bottom_2 no-focus">More</a>
                            </div>
                        </div>

                        <!--[Right] Widgets / Linked Buttons===========================-->
                        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 text-left text-left">
                            <div class="col-md-12 margin_bottom_10">
                                <div class=" row box-shadow-6" style="height: 100%">
                                    <ul class="no-margin no-padding list-2 google_pragati_narrow hidden-sm hidden-xs">
                                        @foreach ($links->get('quick_right', []) as $l)
                                            <li class="bg_red_site text-right"><a href="{{ $l->href() }}" class="lnk-3-1">{{ $l->title }} @if ($l->image)<img src="{{ $l->imageUrl() }}" class="bg_blue_site_1 icon_img_2" alt="">@endif</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <!--Widget-1=================-->
                            <x-front.widget color="green-color" shadow="box-shadow-6" title="Important Govt. Websites" :links="$links->get('govt_links', [])" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--- message from IGP section--->
<div class="row second_look margin_bottom_1" id="igpmessage" style="background-size:cover">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 bg_trans_black_70">
        <div class="fr_lr">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="col-md-12 padding-bottom-5-prcnt">
                        <div class="col-md-12 padding-6-0-prcnt">
                            <h1 class=" heading-1 padding-3-0-prcnt white text-center google_marcellus_SC size-3-vw">
                                <span class="text_shadow_4">&nbsp; {{ setting('home_message_heading') }} &nbsp;</span>
                            </h1>
                            <div class="clearfix bg_white">
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 text-center bg_blue_gray min-height-600">
                                    <div class=" padding-15-15 margin_top_10 bg_white" style="border-width: 10px 1px 10px 1px; border-style: solid; border-color: #606060; -webkit-border-radius:15px;-moz-border-radius:15px ;border-radius: 15px;">
                                        <div class="margin_bottom_10">
                                            <div style="height: 180px;" class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                                @if ($igp) {!! $igp->pictureHtml('height_full img-responsive center-block') !!} @endif
                                            </div>
                                            <div style="height: 180px; vertical-align:bottom;" class="col-lg-6 col-md-6 col-sm-6 col-xs-12 relative">
                                                <img src="{{ asset('assets/images/police-logo-circular.png') }}" class="img-responsive center-block" height="100%">
                                                <p class="google_pragati_narrow size-20 blue_site absolute width_full" style="bottom:0; line-height: 23px;">Superintendent of Police</p>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                        <h2 class=" size-2-vw bg_gray_gradient_1 padding-10-0 ">{{ $igp?->title }}</h2>
                                    </div>
                                </div>
                                <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 min-height-600" style="background: url('{{ asset('assets/images/floral-2-10.png') }}') left top no-repeat,url('{{ asset('assets/images/floral-3-5-1.png') }}') right bottom no-repeat;">
                                    <h1 class=" heading-6 padding-3-0-prcnt blue_site google_courgette size-3-vw"></h1>
                                    <div class="box_msg text-left size-18" style="border: 5px double #606060; padding: 20px; margin-bottom: 20px;">
                                        {!! $igp?->details !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--- mission vision section--->
<div class="row third_look padding-top-6-prcnt padding-bottom-6-prcnt" id="missionvision" style="background-size: cover;">
    <div class="container-fluid ">
        <div class="col-md-12 ">
            <div class="fr_lr">
                <div class=" padding-10-0-prcnt">
                    <!--Mission-->
                    <div class="col-md-6 col-sm-6 col-xs-12 text-right">
                        <div class="padding-7-0-prcnt border_bottom_1 margin_bottom_10" style="height: auto;">
                            <span class="mission_head">
                                <span class="mission_head_child google_pinyon_script black">{{ $mission?->title }}</span>
                            </span>
                        </div>
                        <div class="text-right-mission">
                            <p class="size-2-em black google_pragati_narrow">{!! $mission?->details !!}</p>
                        </div>
                    </div>
                    <!--Vision-->
                    <div class="col-md-6 col-sm-6 col-xs-12 text-left">
                        <div class="padding-7-0-prcnt border_bottom_1 margin_bottom_10" style="height: auto;">
                            <span class="vision_head">
                                <span class="vision_head_child google_pinyon_script black">{{ $vision?->title }}</span>
                            </span>
                        </div>
                        <div class="text-left-vision">
                            <p class="size-2-em black google_pragati_narrow">{!! $vision?->details !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row fourth_look  bg_gray_gradient_2" id="history">
    <!--===== History =====-->
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
        <div class="fr_lr">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="col-md-12">
                        <div class=" col-md-12 padding-6-0-prcnt text-center border_bottom">
                            <h1 class="heading-1 padding-0-0-2-0-prcnt blue_site google_marcellus_SC size-3-vw">
                                <span>&nbsp; {{ $history?->title }} &nbsp;</span>
                            </h1>
                            <div class="row padding-3-0-0-0-prcnt">
                                <div class="col-md-12 margin_bottom_25 hide_in_700">
                                    @if ($history) {!! $history->pictureHtml() !!} @endif
                                </div>
                            </div>
                            <div class="text-justify blue_site margin_bottom_25 size-18">
                                <p>{{ $history?->metadesc }}</p>
                            </div>
                            <div>
                                <a href="{{ route('front.content.view', setting('home_history_content')) }}" class="btn button_1">Details <i class="fa fa-angle-double-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--units section============-->
<div class="row fifth_look bg_img_10_1" id="units">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 padding-9-0-prcnt padding-top-6-prcnt bg_trans_blue_90">
        <div class="fr_lr">
            <div class="container-fluid padding-top-6-prcnt padding-bottom-5-prcnt">
                <div class="col-md-12">
                    <h1 class="heading-1 google_marcellus_SC size-3-vw">
                        <span class="white">&nbsp; {{ setting('home_units_heading') }} &nbsp;</span>
                    </h1>
                    <div class="col-md-12">
                        <div class="padding-7-0-prcnt">
                            <div id="UnitCarousel" class="carouselTicker relative bg_trans_blue_60 padding-3-3-prcnt" style="padding-top:10px;">
                                <ul class="carouselTicker__list " style="height: 145px; overflow: hidden !important;">
                                    @foreach ($units as $b)
                                        <li class="carouselTicker__item lnk-3-1" style="height: 145px;">
                                            <a href="{{ $b->clickurl }}" class="bg_white padding-10 block" target="_blank"><img src="{{ $b->imageUrl() }}" alt="{{ $b->title }}" class="img-responsive" title="{{ $b->title }}"></a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--- important links section--->
<div class="row" id="importantLinks">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 bg_img_4">
        <div class="row padding-10-0-prcnt padding-top-6-prcnt padding-bottom-5-prcnt bg_trans_white_80" >
            <div class="fr_lr">
                <h1 class="heading-1 padding-0-0-5-0-prcnt padding-bottom-5-prcnt blue_site google_marcellus_SC size-3-vw">
                    <span>&nbsp; {{ setting('home_links_heading') }}&nbsp;</span>
                </h1>
                <div class="container-fluid">
                    <div class="col-md-12">
                        <div class="col-lg-12 col-md-12">
                            @foreach ($links->get('important', []) as $l)
                                <x-front.important-tile :link="$l" />
                            @endforeach
                            <div class="clearfix"></div>

                            <div class="width_full text-center">
                                <button class="btn button_2 showbtn">Show More</button>
                                <button class="btn button_2 hidebtn">Show Less</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if (setting('map_embed_url'))
<div class="row fourth_look  bg_gray_gradient_2" id="map">
    <!--===== GOOGLE MAP =====-->
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
        <div class="fr_lr">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="col-md-12">
                        <div class=" col-md-12 padding-6-0-prcnt text-center border_bottom">
                            <h1 class="heading-1 padding-0-0-2-0-prcnt blue_site google_marcellus_SC size-3-vw">
                                <span>&nbsp; {{ setting('map_heading') }} </span>
                            </h1>
                            <div class="text-justify blue_site margin_bottom_25 size-18">
                                <iframe src="{{ setting('map_embed_url') }}" width="1200" height="500" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
