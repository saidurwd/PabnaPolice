<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Options;
use App\Admin\ResourceHooks;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResourceRequest;
use App\Models\User;
use App\Support\LegacyPassword;
use App\Support\SiteCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** One controller serves every CRUD screen described in App\Admin\ResourceRegistry. */
class ResourceController extends Controller
{
    public function index(Request $request): View
    {
        $def = $request->attributes->get('resource');
        $query = $def['model']::query()->with($def['with'] ?? []);

        if (($q = trim((string) $request->query('q'))) !== '' && $def['search']) {
            $query->where(function ($w) use ($def, $q) {
                foreach ($def['search'] as $col) {
                    $w->orWhere($col, 'like', "%{$q}%");
                }
            });
        }

        foreach ($def['filters'] as $col => $f) {
            if (($v = $request->query($col)) !== null && $v !== '') {
                $query->where($col, $v);
            }
        }

        if (! empty($def['softDeletes']) && $request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $query->orderBy($def['order'][0], $def['order'][1]);
        if (isset($def['order'][2])) {
            $query->orderBy($def['order'][2]);
        }
        $query->orderByDesc('id');

        return view('admin.resource.index', [
            'def' => $def,
            'rows' => $query->paginate($def['perPage'])->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $def = $request->attributes->get('resource');
        $model = new $def['model'];

        foreach ($def['fields'] as $f) {
            if ($f['name'] === 'status' && $f['options'] === 'status') {
                $model->status = 'Active';
            }
            if ($f['name'] === 'status_id') {
                $model->status_id = 1;   // "Active" in user_statuses
            }
            if ($f['name'] === 'country_id') {
                $model->country_id = 18; // Bangladesh, the default in the legacy schema
            }
        }

        return view('admin.resource.form', ['def' => $def, 'model' => $model]);
    }

    public function store(ResourceRequest $request): RedirectResponse
    {
        $def = $request->attributes->get('resource');

        return $this->persist($request, $def, new $def['model'], true);
    }

    public function edit(Request $request, string $resource, int $id): View
    {
        $def = $request->attributes->get('resource');

        return view('admin.resource.form', ['def' => $def, 'model' => $def['model']::findOrFail($id)]);
    }

    public function update(ResourceRequest $request, string $resource, int $id): RedirectResponse
    {
        $def = $request->attributes->get('resource');

        return $this->persist($request, $def, $def['model']::findOrFail($id), false);
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $def = $request->attributes->get('resource');
        $model = $def['model']::findOrFail($id);

        if (isset($def['guard']) && ($why = $def['guard']($model))) {
            return back()->with('error', "Cannot delete: {$why}");
        }

        // Uploaded files are deliberately kept on disk (they may still be linked from content).
        $model->delete();
        Options::forget();
        SiteCache::flush();

        $message = ! empty($def['softDeletes']) ? "{$def['title']} moved to the trash (it can be restored)." : "{$def['title']} deleted.";

        return redirect()->route('admin.resource.index', $def['slug'])->with('success', $message);
    }

    public function restore(Request $request, string $resource, int $id): RedirectResponse
    {
        $def = $request->attributes->get('resource');
        abort_unless(! empty($def['softDeletes']), 404);

        $def['model']::onlyTrashed()->findOrFail($id)->restore();
        SiteCache::flush();

        return redirect()->route('admin.resource.index', [$def['slug'], 'trashed' => 1])->with('success', "{$def['title']} restored.");
    }

    private function persist(ResourceRequest $request, array $def, Model $model, bool $isNew): RedirectResponse
    {
        $fields = array_filter($def['fields'], fn ($f) => empty($f['readonly']));

        $this->guardTreeParent($request, $def, $model);

        // The last Super User can't be demoted: nobody would be able to manage roles any more.
        if ($def['slug'] === 'user' && $model instanceof User && $model->exists && $model->isLastSuperUser()
            && $request->input('role') !== config('admin.super_role')) {
            throw ValidationException::withMessages(['role' => 'This is the last Super User, so it must keep that role.']);
        }

        $data = [];
        $uploads = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            if (! empty($f['virtual'])) {
                continue;   // not a database column; handled in ResourceHooks::saved (e.g. the user's role)
            }
            switch ($f['type']) {
                case 'password':
                    if (filled($request->input($name))) {
                        $data[$name] = LegacyPassword::make($request->input($name));
                    }
                    break;
                case 'image':
                case 'file':
                    if ($request->hasFile($name)) {
                        // Read size/type first: the temp file is gone once it has been moved.
                        $uploads[$name] = ['mime' => $request->file($name)->getClientMimeType(), 'size' => $request->file($name)->getSize()];
                        $data[$name] = $this->storeUpload($request->file($name), $f);
                    }
                    break;
                case 'bool':
                    $data[$name] = $request->boolean($name) ? 1 : 0;
                    break;
                case 'datetime':
                    if (filled($request->input($name))) {
                        $data[$name] = Carbon::parse($request->input($name))->format('Y-m-d H:i:s');
                    }
                    break;
                default:
                    $v = $request->input($name);
                    $data[$name] = ($v === '' || $v === null) && str_contains($f['rules'] ?? '', 'nullable') ? null : ($v ?? '');
            }
        }

        if ($isNew) {
            if (! empty($def['audit'])) {
                $data['created_by'] = $request->user()->id;
            }
            if (collect($fields)->contains('name', 'published_at')) {
                $data['published_at'] ??= now()->format('Y-m-d H:i:s');
            }
        }

        foreach ($uploads as $name => $meta) {
            ResourceHooks::uploaded($def['slug'], $data, $name, $meta);
        }

        $model->fill($data);
        $model->save();

        ResourceHooks::saved($def['slug'], $model, $isNew, $request);
        Options::forget();
        SiteCache::flush();

        return redirect()->route('admin.resource.index', $def['slug'])
            ->with('success', "{$def['title']} ".($isNew ? 'created.' : 'updated.'));
    }

    private function guardTreeParent(Request $request, array $def, Model $model): void
    {
        if (empty($def['tree']) || ! $model->exists || blank($request->input('parent_id'))) {
            return;
        }

        $parent = (int) $request->input('parent_id');
        $seen = [];
        while ($parent && ! in_array($parent, $seen, true)) {
            if ($parent === (int) $model->getKey()) {
                throw ValidationException::withMessages(['parent_id' => 'A category cannot be placed under itself or one of its own children.']);
            }
            $seen[] = $parent;
            $parent = (int) $def['model']::whereKey($parent)->value('parent_id');
        }
    }

    private function storeUpload(UploadedFile $file, array $field): string
    {
        $base = preg_replace('/[^\p{L}\p{N}._()\[\]-]+/u', '_', mb_strtolower($file->getClientOriginalName()));
        $name = time().'_'.ltrim($base, '.');
        Storage::disk('uploads')->putFileAs($field['dir'], $file, $name);

        return ($field['store'] ?? null) === 'path' ? "uploads/{$field['dir']}/{$name}" : $name;
    }
}
