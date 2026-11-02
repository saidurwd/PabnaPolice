@props(['color', 'shadow', 'title', 'links'])
<div class="col-md-12 {{ $color }} margin_bottom_10 widget_panel {{ $shadow }}">
    <h1 class="heading-2 padding-10-0 size-20 google_playball cursor-pointer"><span>{{ $title }}</span></h1>
    <div class="widget_description margin_bottom_10 padding-15-15">
        @foreach ($links as $l)
            <a href="{{ $l->href() }}" target="_blank" class="text-left block padding-10-10 margin_bottom_1 google_courgette"><i class="fa fa-angle-double-right"></i>{{ $l->title }}</a>
        @endforeach
    </div>
    <div class="btn  absolute top-0 right-0 collapse_widget no-focus"><i class="fa fa-angle-up"></i></div>
    <div class="btn  absolute top-0 right-0 open_widget no-focus"><i class="fa fa-angle-down"></i></div>
</div>
