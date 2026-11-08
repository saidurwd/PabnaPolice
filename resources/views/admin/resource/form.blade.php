@extends('admin.layouts.app')
@section('title', ($model->exists ? 'Edit ' : 'New ').strtolower($def['title']))
@section('content')
<div class="card"><div class="card-body">
<form method="post" enctype="multipart/form-data"
      action="{{ $model->exists ? route('admin.resource.update', [$def['slug'], $model->getKey()]) : route('admin.resource.store', $def['slug']) }}">
    @csrf @if ($model->exists) @method('PUT') @endif
    <div class="row g-3">
    @foreach ($def['fields'] as $f)
        @php($name = $f['name'])
        @php($val = enum_value(old($name, $f['type'] === 'password' ? '' : $model->{$name})))
        @php($full = in_array($f['type'], ['richtext', 'textarea']))
        <div class="{{ $full ? 'col-12' : 'col-md-6' }}">
            @if ($f['type'] === 'bool')
                <div class="form-check form-switch pt-md-4">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="f_{{ $name }}" name="{{ $name }}" value="1" @checked($val)>
                    <label class="form-check-label" for="f_{{ $name }}">{{ $f['label'] }}</label>
                </div>
            @else
                <label class="form-label" for="f_{{ $name }}">{{ $f['label'] }}@if (str_contains($f['rules'] ?? '', 'required') || (($f['required_on'] ?? '') === 'create' && ! $model->exists)) <span class="text-danger">*</span>@endif</label>
                @switch($f['type'])
                    @case('select')
                        <select class="form-select @error($name) is-invalid @enderror" id="f_{{ $name }}" name="{{ $name }}" @disabled(! empty($f['readonly']))>
                            @if (str_contains($f['rules'] ?? '', 'nullable') || isset($f['empty']))<option value="">{{ $f['empty'] ?? '—' }}</option>@endif
                            @foreach (\App\Admin\Options::get($f['options']) as $k => $label)
                                @if (! ($model->exists && $name === 'parent_id' && (string) $k === (string) $model->getKey()))
                                    <option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select> @break
                    @case('textarea')
                        <textarea class="form-control @error($name) is-invalid @enderror" id="f_{{ $name }}" name="{{ $name }}" rows="4" @readonly(! empty($f['readonly']))>{{ $val }}</textarea> @break
                    @case('richtext')
                        <textarea class="form-control js-rich @error($name) is-invalid @enderror" id="f_{{ $name }}" name="{{ $name }}" rows="14">{{ $val }}</textarea> @break
                    @case('image')
                    @case('file')
                        <input type="file" class="form-control @error($name) is-invalid @enderror" id="f_{{ $name }}" name="{{ $name }}" @if ($f['type'] === 'image') accept="image/*" @endif>
                        @if ($model->exists && $model->{$name})
                            @php($u = ($f['store'] ?? null) === 'path' ? asset($model->{$name}) : upload_url($f['dir'], $model->{$name}))
                            <div class="mt-2 small">Current:
                                @if ($u) @if ($f['type'] === 'image')<img class="thumb" src="{{ $u }}" alt="">@endif <a href="{{ $u }}" target="_blank">{{ basename($model->{$name}) }}</a>
                                @else <span class="text-danger">{{ $model->{$name} }} (file missing on disk)</span> @endif
                            </div>
                        @endif @break
                    @case('datetime')
                        <input type="datetime-local" class="form-control @error($name) is-invalid @enderror" id="f_{{ $name }}" name="{{ $name }}"
                               value="{{ $val ? \Illuminate\Support\Carbon::parse($val)->format('Y-m-d\TH:i') : ($model->exists ? '' : now()->format('Y-m-d\TH:i')) }}">
                        @break
                    @default
                        <input type="{{ $f['type'] === 'password' ? 'password' : ($f['type'] === 'number' ? 'number' : ($f['type'] === 'email' ? 'email' : 'text')) }}"
                               class="form-control @error($name) is-invalid @enderror" id="f_{{ $name }}" name="{{ $name }}" value="{{ $f['type'] === 'password' ? '' : $val }}"
                               @readonly(! empty($f['readonly'])) @if ($f['type'] === 'password') autocomplete="new-password" @endif>
                @endswitch
                @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @isset($f['help'])<div class="form-text">{{ $f['help'] }}</div>@endisset
            @endif
        </div>
    @endforeach
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.resource.index', $def['slug']) }}">Cancel</a>
    </div>
</form>
</div></div>
@endsection
@if (collect($def['fields'])->contains('type', 'richtext'))
@push('scripts')
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
tinymce.init({
    selector: 'textarea.js-rich', license_key: 'gpl', height: 480, promotion: false, branding: false,
    base_url: '{{ asset('vendor/tinymce') }}', suffix: '.min', relative_urls: false, convert_urls: false,
    plugins: 'lists link image table code autolink media charmap fullscreen anchor searchreplace wordcount',
    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media table | code fullscreen',
    valid_elements: '*[*]', extended_valid_elements: '*[*]',
    images_upload_handler: (blob) => new Promise((resolve, reject) => {
        const fd = new FormData(); fd.append('file', blob.blob(), blob.filename());
        fetch('{{ route('admin.editor-upload') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}, body: fd})
            .then(r => r.ok ? r.json() : Promise.reject('Upload failed')).then(j => resolve(j.location)).catch(reject);
    }),
});
</script>
@endpush
@endif
