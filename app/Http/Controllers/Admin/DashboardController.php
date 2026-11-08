<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditTrail;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Content;
use App\Models\Document;
use App\Models\Feedback;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\HealthCheck;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $weekAgo = now()->subDays(7);

        // Each tile: label, count, icon, colour, link slug, small hint under the number.
        $newContent = Content::where('published_at', '>=', $weekAgo)->count();
        $unread = Feedback::where('is_read', false)->count();
        $stats = [
            ['Content', Content::count(), 'bi-file-earmark-text', 'primary', 'content', $newContent ? "+{$newContent} this week" : 'No new this week'],
            ['Banners & gallery', Banner::count(), 'bi-images', 'info', 'banner', null],
            ['Documents', Document::count(), 'bi-file-earmark-pdf', 'warning', 'document', null],
            ['Users', User::count(), 'bi-people', 'success', 'user', null],
            ['Unread feedback', $unread, 'bi-chat-dots', $unread ? 'danger' : 'secondary', 'feedback', $unread ? 'Needs a reply' : 'All caught up'],
        ];
        $stats = array_values(array_filter($stats, fn ($s) => $user->can($s[4].'.view')));

        $quick = array_values(array_filter([
            ['New content', 'bi-plus-circle', route('admin.resource.create', 'content'), 'content.create'],
            ['Upload document', 'bi-upload', route('admin.resource.create', 'document'), 'document.create'],
            ['Add banner', 'bi-image', route('admin.resource.create', 'banner'), 'banner.create'],
            ['Site settings', 'bi-sliders', route('admin.settings'), 'settings.view'],
        ], fn ($q) => $user->can($q[3])));

        $byCategory = Content::select('category_id', DB::raw('count(*) as n'))->groupBy('category_id')->orderByDesc('n')->limit(6)->toBase()->get();
        $categoryNames = Category::whereIn('id', $byCategory->pluck('category_id'))->pluck('title', 'id');

        $canActivity = $user->can('activity-log.view') || $user->can('audit-log.view');

        return view('admin.dashboard', [
            'stats' => $stats,
            'quick' => $quick,
            'recent' => Content::with('category')->orderByDesc('id')->limit(7)->get(),
            'categories' => $byCategory->map(fn ($r) => ['title' => $categoryNames[$r->category_id] ?? 'Uncategorised', 'n' => $r->n]),
            'signIns' => AuditTrail::with('user')->orderByDesc('id')->limit(6)->get(),
            'activity' => $canActivity ? ActivityLog::orderByDesc('id')->limit(7)->get() : null,
            'failedSignIns' => $user->can('security-events.view')
                ? SecurityEvent::whereIn('type', ['login_failed', 'login_locked'])->where('created_at', '>=', now()->subDay())->count()
                : null,
            // The self-checks touch the disk and database, so reuse a result up to five minutes old.
            'health' => $user->can('system-health.view') ? Cache::remember('dashboard.health', 300, fn () => HealthCheck::overall((new HealthCheck)->run())) : null,
        ]);
    }
}
