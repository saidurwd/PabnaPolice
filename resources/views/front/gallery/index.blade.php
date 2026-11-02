@extends('front.layouts.inner')
@section('title', $category?->title)
@section('heading', $category?->title)
@section('inner')
    <div class="list-view">
        @if ($rows->total())
            <div class="summary">Displaying {{ $rows->firstItem() }}-{{ $rows->lastItem() }} of {{ $rows->total() }} {{ $rows->total() === 1 ? 'result' : 'results' }}.</div>
        @endif
        <div class="items">
            @forelse ($rows as $b)
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6" style="margin-bottom: 10px;">
                    <a class="border-radius-50-prcnt overflow-hidden text-center img_anchor_1" data-lightbox="default" data-title="{{ $b->title }}" href="{{ asset('uploads/banner/'.$b->banner) }}">
                        <img src="{{ asset('uploads/banner/'.$b->banner) }}" alt="{{ $b->title }}" class="width_full" title="{{ $b->title }}">
                    </a>
                </div>
            @empty
                <span class="empty">No results found.</span>
            @endforelse
        </div>
        <div class="clearfix"></div>
        <div class="pager">{{ $rows->links() }}</div>
    </div>
@endsection
