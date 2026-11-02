<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['groups' => config('site')]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (config('site') as $fields) {
            foreach ($fields as $name => $def) {
                $rules[$name] = match ($def['type']) {
                    'number' => 'nullable|integer|min:0',
                    'url' => 'nullable|max:2000',
                    default => 'nullable|string',
                };
            }
        }
        $data = $request->validate($rules);

        foreach ($data as $name => $value) {
            Setting::put($name, $value);
        }

        return back()->with('success', 'Settings saved.');
    }
}
