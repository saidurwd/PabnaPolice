@props(['links'])
        <!--==== Footer top ====-->
        <div class="container-fluid">
            <div class="row third_look ">
                <div class="col-xl-12 col-lg-12 col-md-12 col-12 bg_blue_site padding-bottom-0">
                    <div class="container-fluid margin-bottom-0 padding-bottom-0">
                        <div class="col-xl-4 col-lg-4 col-md-12 col-12 text-center-in-sm">
                            <div class=" col-lg-12" style="line-height: auto;"></div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-12 col-12 relative hide_in_1050">
                            <div class="col-lg-12 col-12 text-center absolute z-index-100" style="top:15px; left: 0;">
                                <img src="{{ asset('assets/images/police-logo-circular.png') }}" class="mx-auto d-block" alt="Bangladesh Police logo">
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-12 col-12">
                            <div class=" col-lg-12 col-12 social_1 text-center-in-sm text-right-in-md text-right-in-lg">
                                <a href="{{ setting('facebook_url') }}" class="facebook_icon" target="_blank">&nbsp;</a>
                                <a href="{{ setting('youtube_url') }}" class="youtube_icon" target="_blank">&nbsp;</a>
                                <a href="{{ setting('twitter_url') }}" class="twitter_icon" target="_blank">&nbsp;</a>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix">&nbsp;</div>
                </div>
                <!--==== Footer Main ====-->
                <div class="col-xl-12 col-lg-12 col-md-12 col-12 footer_bg">
                    <div class="row footer_content" >
                        <div class="fr_lr">
                            <div style="height: 40px;">&nbsp;</div>
                            <div class="container-fluid no-padding-left">
                                <div class="col-lg-12 no-padding-left">
                                    <div class="col-lg-12 no-padding-left">
                                        <div class="col-lg-8 offset-lg-0 col-md-11 offset-md-1 col-11 offset-1">
                                            <div class="row">
                                                <div class="d-none d-lg-block " style="height: 60px;">&nbsp;</div>
                                                <div class="col-lg-12 column-3">
                                                    <ul class="list-2 ">
                                                        @foreach ($links->get('footer_links', []) as $l)
                                                            <li><a href="{{ $l->href() }}" class="lnk-1">{{ $l->title }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <x-front.quick-contact />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--==== Footer Finishing line ====-->
                <div class="col-xl-12 col-lg-12 col-md-12 col-12" style="background: #092b41;" id="bottom">
                    <div class="container-fluid">
                        <div class="col-lg-12 text-center gray google_pragati_narrow size-16" style="padding: 10px 0;">
                            Copyright &copy; {{ date('Y') }} {{ setting('site_name') }}. {{ setting('footer_credit') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

