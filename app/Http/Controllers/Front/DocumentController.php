<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request, int $id)
    {
        $rows = Document::active()->where('category_id', $id)
            ->when($request->query('title'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($request->query('date'), fn ($q, $v) => $q->where('published_at', 'like', '%'.$v.'%'))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate((int) setting('page_size', 25))->withQueryString();

        return view('front.document.index', ['category' => DocumentCategory::find($id), 'rows' => $rows]);
    }

    public function download(int $id)
    {
        $doc = Document::active()->findOrFail($id);
        $disk = Storage::disk('uploads');
        abort_if(empty($doc->document_file) || ! $disk->exists($doc->storagePath()), 404);

        return $disk->download($doc->storagePath(), basename($doc->document_file));
    }
}
