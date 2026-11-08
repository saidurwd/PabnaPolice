<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Content;
use App\Models\Feedback;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $content = fn (string $key) => Content::find((int) setting($key));

        return view('front.home', [
            'igp' => $content('home_igp_content'),
            'mission' => $content('home_mission_content'),
            'vision' => $content('home_vision_content'),
            'history' => $content('home_history_content'),
            'slider' => Banner::active()->where('category_id', (int) setting('home_slider_category'))->inRandomOrder()->limit(10)->get(),
            'units' => Banner::active()->where('category_id', (int) setting('home_units_category'))->inRandomOrder()->limit(10)->get(),
            'recentCategory' => Category::find((int) setting('home_recent_category')),
            'recent' => Content::active()->where('category_id', (int) setting('home_recent_category'))
                ->orderByDesc('published_at')->orderByDesc('id')->limit((int) setting('home_recent_limit', 4))->get(),
        ]);
    }

    public function contact(Request $request)
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $request->session()->put('contact_captcha', $a + $b);

        return view('front.contact', ['captcha' => "{$a} + {$b}"]);
    }

    public function contactSend(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'subject' => 'required|string|max:128',
            'body' => 'required|string|max:5000',
            'captcha' => 'required',
        ]);

        // Honeypot field and arithmetic check keep bots out without a third-party captcha.
        if ($request->filled('website') || (int) $data['captcha'] !== (int) $request->session()->pull('contact_captcha')) {
            return redirect()->route('front.contact')->withInput()->withErrors(['captcha' => 'The verification answer is wrong.']);
        }

        Feedback::create([
            'name' => $data['name'], 'email' => $data['email'], 'subject' => $data['subject'],
            'message' => $data['body'], 'ip' => $request->ip(),
        ]);

        return redirect()->route('front.contact')->with('contact', 'Thank you for contacting us. We will respond to you as soon as possible.');
    }

    /** Footer "Quick Contact" form (posted with jQuery). */
    public function feedback(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:50',
            'nid' => 'nullable|string|max:50',
            'message' => 'required|string|max:5000',
        ]);

        Feedback::create($data + ['ip' => $request->ip()]);

        return response()->json(['ok' => true]);
    }
}
