<?php

namespace App\Services;

use App\Models\Tenant;

class SettingsService
{
    // All keys with their default values
    public const DEFAULTS = [
        // Organisation
        'org_sector'                  => 'education',
        'org_website'                 => '',
        'org_established_year'        => '',
        'org_address'                 => '',
        'org_logo_url'                => '',

        // Meeting
        'meeting_quorum_percent'      => '51',
        'meeting_notice_days'         => '7',
        'meeting_default_duration'    => '120',
        'meeting_allow_virtual'       => '1',
        'meeting_rsvp_required'       => '1',
        'meeting_max_guests'          => '5',

        // Terminology
        'label_board'                 => 'Board',
        'label_chairperson'           => 'Chairperson',
        'label_secretary'             => 'Board Secretary',
        'label_member'                => 'Board Member',
        'label_meeting'               => 'Meeting',

        // Agenda / Minutes
        'agenda_show_naac'            => '0',
        'agenda_show_hod_resolution'  => '0',
        'agenda_show_action'          => '1',
        'agenda_show_decisions'       => '1',
        'agenda_custom_label_1'       => '',
        'agenda_custom_label_2'       => '',

        // Features
        'feature_committees'          => '1',
        'feature_departments'         => '1',
        'feature_circular_resolution' => '1',
        'feature_action_items'        => '1',
        'feature_board_packs'         => '1',
        'feature_minutes'             => '1',
        'feature_analytics'           => '1',

        // Notifications
        'notify_meeting_reminder'     => '1',
        'notify_reminder_days'        => '3',
        'notify_action_item_reminder' => '1',
        'notify_action_due_days'      => '2',
        'notify_minutes_on_approval'  => '1',
        'notify_circular_resolution'  => '1',
    ];

    // Sector presets — applied when sector changes
    public const SECTOR_PRESETS = [
        'education' => [
            'label_board'                => 'Board of Management',
            'label_chairperson'          => 'Chairperson',
            'label_secretary'            => 'Board Secretary',
            'label_member'               => 'Board Member',
            'label_meeting'              => 'Board Meeting',
            'agenda_show_naac'           => '1',
            'agenda_show_hod_resolution' => '1',
        ],
        'corporate' => [
            'label_board'                => 'Board of Directors',
            'label_chairperson'          => 'Chairman',
            'label_secretary'            => 'Company Secretary',
            'label_member'               => 'Director',
            'label_meeting'              => 'Board Meeting',
            'agenda_show_naac'           => '0',
            'agenda_show_hod_resolution' => '0',
        ],
        'ngo' => [
            'label_board'                => 'Executive Committee',
            'label_chairperson'          => 'President',
            'label_secretary'            => 'Secretary',
            'label_member'               => 'Committee Member',
            'label_meeting'              => 'Committee Meeting',
            'agenda_show_naac'           => '0',
            'agenda_show_hod_resolution' => '0',
        ],
        'government' => [
            'label_board'                => 'Governing Council',
            'label_chairperson'          => 'Chairperson',
            'label_secretary'            => 'Member Secretary',
            'label_member'               => 'Council Member',
            'label_meeting'              => 'Council Meeting',
            'agenda_show_naac'           => '0',
            'agenda_show_hod_resolution' => '0',
        ],
        'other' => [
            'label_board'                => 'Board',
            'label_chairperson'          => 'Chairperson',
            'label_secretary'            => 'Secretary',
            'label_member'               => 'Member',
            'label_meeting'              => 'Meeting',
            'agenda_show_naac'           => '0',
            'agenda_show_hod_resolution' => '0',
        ],
    ];

    private Tenant $tenant;
    private array  $cache = [];

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public static function for(Tenant $tenant): self
    {
        return new self($tenant);
    }

    public static function current(): self
    {
        return new self(auth()->user()->tenant);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (empty($this->cache)) {
            $this->cache = $this->tenant->settings()
                ->pluck('value', 'key')
                ->toArray();
        }

        return $this->cache[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    public function int(string $key): int
    {
        return (int) $this->get($key);
    }

    public function all(): array
    {
        $stored = $this->tenant->settings()->pluck('value', 'key')->toArray();
        return array_merge(self::DEFAULTS, $stored);
    }

    public function saveAll(array $data): void
    {
        foreach ($data as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                $this->tenant->setSetting($key, $value ?? '');
            }
        }
        $this->cache = [];
    }

    public function applySectorPreset(string $sector): void
    {
        $preset = self::SECTOR_PRESETS[$sector] ?? self::SECTOR_PRESETS['other'];
        foreach ($preset as $key => $value) {
            $this->tenant->setSetting($key, $value);
        }
        $this->cache = [];
    }
}
