<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Content;
use App\Models\Document;
use App\Models\Feedback;
use App\Models\User;
use App\Models\Visitor;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'stats' => [
                ['Content', Content::count(), 'bi-file-earmark-text', 'content'],
                ['Banners / gallery', Banner::count(), 'bi-images', 'banner'],
                ['Documents', Document::count(), 'bi-file-earmark-pdf', 'document'],
                ['Users', User::count(), 'bi-person', 'user'],
                ['Unread feedback', Feedback::where('is_read', false)->count(), 'bi-chat-dots', 'feedback'],
            ],
            'recent' => Content::orderByDesc('id')->limit(8)->get(),
            'visits' => Visitor::orderByDesc('id')->limit(8)->get(),
        ]);
    }
}
