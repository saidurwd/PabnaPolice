<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\Logs\Security;
use App\Support\Logs\UserAgent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The three log screens: Audit log (data changes), Activity log (user actions), Security events. */
class LogController extends Controller
{
    private const PER_PAGE = 30;

    private const EXPORT_LIMIT = 20000;

    private const ACTIVITY_HEADER = ['Time', 'Event', 'User', 'Record', 'Description', 'IP address', 'Browser', 'Changes'];

    public function audit(Request $request)
    {
        $query = ActivityLog::where('log', 'audit')->orderByDesc('id');
        $this->common($query, $request, ['description', 'subject_label', 'user_name']);
        $this->filterEq($query, $request, ['event', 'subject_type']);

        return $this->respond($request, 'audit', 'Audit log', $query, [
            'events' => ['created' => 'Created', 'updated' => 'Updated', 'trashed' => 'Moved to trash', 'restored' => 'Restored', 'deleted' => 'Deleted'],
            'subjects' => ActivityLog::where('log', 'audit')->distinct()->orderBy('subject_type')->pluck('subject_type', 'subject_type')->all(),
        ], fn (ActivityLog $r) => [$r->created_at, $r->event, $r->user_name, trim(($r->subject_type ?? '').' '.($r->subject_label ?? '')), $r->description, $r->ip_address, UserAgent::label($r->user_agent), $r->properties ? json_encode($r->properties, JSON_UNESCAPED_UNICODE) : ''],
            self::ACTIVITY_HEADER);
    }

    public function activity(Request $request)
    {
        $query = ActivityLog::where('log', 'activity')->orderByDesc('id');
        $this->common($query, $request, ['description', 'user_name', 'event']);
        $this->filterEq($query, $request, ['event']);

        return $this->respond($request, 'activity', 'Activity log', $query, [
            'events' => ActivityLog::where('log', 'activity')->distinct()->orderBy('event')->pluck('event', 'event')->all(),
        ], fn (ActivityLog $r) => [$r->created_at, $r->event, $r->user_name, trim(($r->subject_type ?? '').' '.($r->subject_label ?? '')), $r->description, $r->ip_address, UserAgent::label($r->user_agent), $r->properties ? json_encode($r->properties, JSON_UNESCAPED_UNICODE) : ''],
            self::ACTIVITY_HEADER);
    }

    public function security(Request $request)
    {
        $query = SecurityEvent::orderByDesc('id');
        $this->common($query, $request, ['message', 'identifier']);
        $this->filterEq($query, $request, ['type', 'severity']);

        return $this->respond($request, 'security', 'Security events', $query, [
            'types' => Security::TYPES,
            'severities' => ['info' => 'Info', 'warning' => 'Warning', 'critical' => 'Critical'],
        ], fn (SecurityEvent $r) => [$r->created_at, $r->severity, $r->type, $r->message, $r->user_id, $r->identifier, $r->ip_address, UserAgent::label($r->user_agent)],
            ['Time', 'Severity', 'Type', 'Message', 'User ID', 'Identifier', 'IP address', 'Browser']);
    }

    /** Search box, user, IP address and date range: shared by all three screens. */
    private function common(Builder $query, Request $request, array $searchColumns): void
    {
        if (filled($q = $request->query('q'))) {
            $query->where(function (Builder $w) use ($searchColumns, $q) {
                foreach ($searchColumns as $col) {
                    $w->orWhere($col, 'like', '%'.addcslashes($q, '%_\\').'%');
                }
            });
        }
        if (filled($user = $request->query('user_id'))) {
            $query->where('user_id', (int) $user);
        }
        if (filled($ip = $request->query('ip'))) {
            $query->where('ip_address', 'like', addcslashes(trim($ip), '%_\\').'%');
        }
        if ($from = $this->date($request->query('from'))) {
            $query->where('created_at', '>=', $from->startOfDay());
        }
        if ($to = $this->date($request->query('to'))) {
            $query->where('created_at', '<=', $to->endOfDay());
        }
    }

    private function filterEq(Builder $query, Request $request, array $columns): void
    {
        foreach ($columns as $col) {
            if (filled($v = $request->query($col))) {
                $query->where($col, $v);
            }
        }
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse((string) $value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query
     * @param  \Closure(T): array<int, mixed>  $csvRow
     * @param  array<int, string>  $csvHeader
     */
    private function respond(Request $request, string $kind, string $title, Builder $query, array $options, \Closure $csvRow, array $csvHeader)
    {
        if ($request->query('export') === 'csv') {
            return $this->csv($kind, $query, $csvRow, $csvHeader);
        }

        return view('admin.logs.index', [
            'kind' => $kind,
            'title' => $title,
            'rows' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'users' => User::orderBy('full_name')->pluck('full_name', 'id')->all(),
        ] + $options);
    }

    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query
     * @param  \Closure(T): array<int, mixed>  $row
     * @param  array<int, string>  $header
     */
    private function csv(string $kind, Builder $query, \Closure $row, array $header): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $row, $header) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                                      // UTF-8 BOM so Excel reads Bengali text
            fputcsv($out, $header);

            // A leading = + - @ would be run as a formula by Excel; prefix such cells with an apostrophe.
            $safe = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;

            foreach ($query->limit(self::EXPORT_LIMIT)->cursor() as $r) {
                fputcsv($out, array_map($safe, $row($r)));
            }
            fclose($out);
        }, "{$kind}-log-".now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
