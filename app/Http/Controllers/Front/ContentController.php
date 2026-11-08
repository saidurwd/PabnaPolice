<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function view(int $id)
    {
        return view('front.content.view', ['content' => Content::active()->findOrFail($id)]);
    }

    public function index(Request $request, int $id)
    {
        $rows = Content::active()->where('category_id', $id)
            ->when($request->query('title'), fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($request->query('date'), fn ($q, $v) => $q->where('published_at', 'like', '%'.$v.'%'))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate((int) setting('page_size', 25))->withQueryString();

        return view('front.content.index', ['category' => Category::find($id), 'rows' => $rows]);
    }
}
