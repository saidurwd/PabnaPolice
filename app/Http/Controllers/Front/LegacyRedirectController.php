<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

/** Old Yii URL shapes that still get requested (bookmarks, search engines). */
class LegacyRedirectController extends Controller
{
    public function content(int $id)
    {
        return redirect()->route('front.content.view', $id, 301);
    }

    public function document(int $id)
    {
        return redirect()->route('front.document.download', $id, 301);
    }
}
