@extends('front.layouts.inner')
@section('title', 'Contact Us')
@section('heading', 'Contact Us')
@section('inner')
    <h1>Contact Us</h1>
    @if (session('contact'))
        <div class="alert alert-success">{{ session('contact') }}</div>
    @else
        <p>If you have business inquiries or other questions, please fill out the following form to contact us. Thank you.</p>
        <form method="post" action="{{ route('front.contact.send') }}" class="form">
            @csrf
            <p class="note">Fields with <span class="required">*</span> are required.</p>
            @if ($errors->any())
                <div class="alert alert-danger"><ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="mb-3"><label for="name">Name <span class="required">*</span></label>
                <input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="150"></div>
            <div class="mb-3"><label for="email">Email <span class="required">*</span></label>
                <input class="form-control" id="email" name="email" value="{{ old('email') }}" maxlength="150"></div>
            <div class="mb-3"><label for="subject">Subject <span class="required">*</span></label>
                <input class="form-control" id="subject" name="subject" value="{{ old('subject') }}" maxlength="128"></div>
            <div class="mb-3"><label for="body">Body <span class="required">*</span></label>
                <textarea class="form-control" id="body" name="body" rows="6">{{ old('body') }}</textarea></div>
            <div class="mb-3"><label for="captcha">Verification: what is {{ $captcha }}? <span class="required">*</span></label>
                <input class="form-control" id="captcha" name="captcha" autocomplete="off" style="max-width:140px"></div>
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @endif
@endsection
