@props(['links'])
<!--Left Fixed panel-->
        <div class="absolute z-index-500 padding-top-1 leftnavbox top-26vh">
            <ul class="list-2 no-padding no-margin">
                <li><a href="{{ setting('facebook_url') }}" class=" padding-2-2 lnk-7-1 cursor-pointer" target="_blank" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-container="body" title="facebook"><img src="{{ asset('assets/images/icons/facebook.jpg') }}" width="40" class="icon_img_3" alt="facebook"> </a></li>
                <li><a href="{{ setting('youtube_url') }}" class=" padding-2-2 lnk-7-1 cursor-pointer" target="_blank" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-container="body" title="youtube"><img src="{{ asset('assets/images/icons/youtube.jpg') }}" width="40" class="icon_img_3" alt="youtube"> </a></li>
                <li><a href="{{ setting('twitter_url') }}" class=" padding-2-2 lnk-7-1 cursor-pointer" target="_blank" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-container="body" title="twitter"><img src="{{ asset('assets/images/icons/twitter.jpg') }}" width="40" class="icon_img_3" alt="twitter"> </a></li>
            </ul>
        </div>
        <!--right Fixed panel-->
        <div class="fixed padding-top-1 rightnavbox top-26vh right-0" id="staticsidenavright" style="z-index: 999;">
            <ul class="list-2 no-padding no-margin">
                <li><span class=" padding-2-2 lnk-7 services cursor-pointer" target="_blank" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-container="body" title="Services"><img src="{{ asset('assets/images/icons/services3_icon.png') }}" width="40" class="icon_img_3" alt="Services"> </span></li>
                <li><span  class=" padding-2-2 lnk-7 info cursor-pointer" target="_blank" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-container="body" title="Info"><img src="{{ asset('assets/images/icons/infolist-1.png') }}" width="40" class="icon_img_3" alt="Info"> </span></li>
                <li><span  class=" padding-2-2 lnk-7 apps cursor-pointer" target="_blank" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-container="body" title="Police Apps"><img src="{{ asset('assets/images/icons/mobile-app.png') }}" width="40" class="bg_gray_white_gray icon_img_3" alt="Police Apps"> </span></li>
            </ul>
        </div>
        <!--Services List-->
        <div class="services_list bg_trans_blue_90">
            <button class="close_service btn bg_grad_5 width_full lnk-2 block no-border-radius no-border"><i class="fa fa-angle-double-right size-25"></i></button>
            <ul class="no-margin no-padding list-2">
                @foreach ($links->get('service_panel', []) as $l)
                    <li class="bg_white text-start"><a href="{{ $l->href() }}" class="lnk-3 size-16">{{ $l->title }}</a> </li>
                @endforeach
            </ul>
        </div>
        <!--Information List-->
        <div class="info_list bg_trans_black_90">
            <button class="close_info btn bg_grad_5 width_full lnk-2 block no-border-radius no-border"><i class="fa fa-angle-double-right size-25"></i></button>
            <ul class="no-margin no-padding list-2">
                @foreach ($links->get('info_panel', []) as $l)
                    <li class="bg_white text-start"><a href="{{ $l->href() }}" class="lnk-3 size-16">{{ $l->title }}</a></li>
                @endforeach
            </ul>
        </div>
        <!--Apps List-->
        <div class="apps_list bg_trans_black_90">
            <button class="close_apps btn btn-danger width_full lnk-2 block no-border-radius"><i class="fa fa-times-circle-o size-25"></i></button>
            <h2 class="heading-1 white size-20 margin_bottom_10"><span> Apps</span></h2>
            <ul class="no-margin no-padding list-2 width_full">
                @foreach ($links->get('app_badge', []) as $l)
                    <li class="text-center width_full"><a href="{{ $l->href() }}" target="_blank" class="lnk-4"><img src="{{ $l->imageUrl() }}" alt="{{ $l->title }}" style="width: auto; max-width:100%"></a></li>
                @endforeach
            </ul>
        </div>

