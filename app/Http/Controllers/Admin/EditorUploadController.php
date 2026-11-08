<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Logs\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Image upload endpoint for the rich-text editor. */
class EditorUploadController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['file' => 'required|image|max:8192']);

        $file = $request->file('file');
        $name = time().'_'.bin2hex(random_bytes(3)).'.'.$file->guessExtension();
        Storage::disk('uploads')->putFileAs('content/editor', $file, $name);

        Activity::record('upload', 'Uploaded an image in the editor', ['file' => $name]);

        return response()->json(['location' => asset("uploads/content/editor/{$name}")]);
    }
}
