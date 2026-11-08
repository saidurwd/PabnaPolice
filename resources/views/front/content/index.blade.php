@extends('front.layouts.inner')
@section('title', $category?->title)
@section('heading', $category?->title)
@section('inner')
    @include('front.partials.grid', ['rows' => $rows, 'linkTo' => fn ($r) => route('front.content.view', $r->id)])
@endsection
