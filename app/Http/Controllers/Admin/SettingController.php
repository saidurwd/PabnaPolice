<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Logs\Activity;
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

        $changed = [];
        foreach ($data as $name => $value) {
            if ((string) Setting::get($name) !== (string) $value) {
                $changed[] = $name;
            }
            Setting::put($name, $value);
        }
        if ($changed) {
            Activity::record('settings_updated', 'Saved site settings ('.count($changed).' changed)', ['changed' => $changed]);
        }

        return back()->with('success', 'Settings saved.');
    }
}
