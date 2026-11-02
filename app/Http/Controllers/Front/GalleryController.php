<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerCategory;

class GalleryController extends Controller
{
    public function index(int $id)
    {
        $rows = Banner::active()->where('category_id', $id)
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate((int) setting('gallery_page_size', 20));

        return view('front.gallery.index', ['category' => BannerCategory::find($id), 'rows' => $rows]);
    }
}
