@extends('admin.layouts.app')
@section('title', 'Site settings')
@section('content')
<form method="post" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')
@foreach ($groups as $group => $fields)
    <div class="card mb-3"><div class="card-header bg-white fw-semibold">{{ $group }}</div><div class="card-body"><div class="row g-3">
        @foreach ($fields as $name => $f)
            <div class="{{ $f['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
                <label class="form-label" for="s_{{ $name }}">{{ $f['label'] }}</label>
                @if ($f['type'] === 'textarea')
                    <textarea class="form-control" rows="3" id="s_{{ $name }}" name="{{ $name }}">{{ old($name, setting($name)) }}</textarea>
                @else
                    <input class="form-control @error($name) is-invalid @enderror" type="{{ $f['type'] === 'number' ? 'number' : 'text' }}" id="s_{{ $name }}" name="{{ $name }}" value="{{ old($name, setting($name)) }}">
                @endif
                @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @isset($f['help'])<div class="form-text">{{ $f['help'] }}</div>@endisset
            </div>
        @endforeach
    </div></div></div>
@endforeach
<button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save settings</button>
</form>
@endsection
