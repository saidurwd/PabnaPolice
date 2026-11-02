<?php

namespace App\Admin;

use App\Models\CategoryNode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Resource-specific behaviour that does not fit the declarative definitions. */
class ResourceHooks
{
    /**
     * Called for each uploaded file before the model is filled; may add columns to $data.
     *
     * @param  array{mime: string, size: int|false}  $meta
     */
    public static function uploaded(string $slug, array &$data, string $field, array $meta): void
    {
        if ($slug === 'document' && $field === 'document_file') {
            $data['document_type'] = $meta['mime'];
            $data['document_size'] = $meta['size'] ?: 0;
        }
    }

    /** Called after the model is saved. */
    public static function saved(string $slug, Model $model, bool $isNew, Request $request): void
    {
        if ($model instanceof User && $request->filled('role')) {
            $model->syncRoles([$request->input('role')]);
        }

        if ($model instanceof CategoryNode) {
            $model->rebuildTree();
        }
    }
}
