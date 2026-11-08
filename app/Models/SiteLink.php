<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $group
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $url
 * @property string|null $image
 * @property bool $is_extra
 * @property int $ordering
 * @property Status $status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class SiteLink extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    public const GROUPS = [
        'quick_left' => 'Home: left buttons (with icon)',
        'quick_right' => 'Home: right buttons (with icon)',
        'service_panel' => 'Slide panel: Services',
        'info_panel' => 'Slide panel: Info',
        'app_badge' => 'Slide panel: Police apps (badge images)',
        'web_links' => 'Home widget: Police Web Links',
        'govt_links' => 'Home widget: Important Govt. Websites',
        'important' => 'Home: Important links tiles',
        'footer_links' => 'Footer links',
        'header_badges' => 'Header: logo badges (e.g. Election, 999)',
        'partner_links' => 'Home sidebar: partner organisations (INTERPOL, UN …)',
        'emergency' => 'Home sidebar: emergency numbers',
    ];

    protected function casts(): array
    {
        return [
            'is_extra' => 'boolean',
            'status' => Status::class,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group)->orderBy('ordering')->orderBy('id');
    }

    /** Absolute URL for stored paths like /content/14.html or full http(s) links. */
    public function href(): string
    {
        $url = (string) $this->url;

        return preg_match('#^(https?:)?//#', $url) || $url === '#' ? $url : url($url);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }
}
