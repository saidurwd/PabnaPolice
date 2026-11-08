@extends('front.layouts.inner')
@section('title', $content->title)
@section('heading', $content->title)
@section('meta_keywords', $content->metakey ?: setting('meta_keywords'))
@section('meta_description', $content->metadesc ?: (str_chars_limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $content->details)))), 160) ?: setting('meta_description')))
@section('og_type', 'article')
@section('og_image', $content->imageUrl() ?? asset('assets/images/police-logo-circular.png'))
@section('inner')
    <div class="float-start" style="padding: 0px 10px 10px 0px;">
        @if ($url = $content->imageUrl())<img src="{{ $url }}" alt="{{ $content->title }}" class="img-fluid" title="{{ $content->title }}">@endif
    </div>
    <div class="float-start">{!! $content->details !!}</div>
@endsection
