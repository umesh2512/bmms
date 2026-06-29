<?php

namespace App\Filament\Tenant\Pages;

use App\Services\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Support\Icons\Heroicon;

class TenantSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon  = Heroicon::OutlinedCog6Tooth;
    protected static string|\UnitEnum|null   $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int    $navigationSort  = 90;

    protected string $view = 'filament.tenant.pages.tenant-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SettingsService::current()->all();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([

                        Tabs\Tab::make('Organisation')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Section::make('Sector & Identity')->schema([
                                    Select::make('org_sector')
                                        ->label('Organisation Sector')
                                        ->options([
                                            'education'  => 'Education (College / University / School)',
                                            'corporate'  => 'Corporate (Company / Business)',
                                            'ngo'        => 'NGO / Trust / Society',
                                            'government' => 'Government / Public Sector',
                                            'other'      => 'Other',
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (string $state, \Filament\Schemas\Components\Utilities\Set $set) {
                                            $preset = SettingsService::SECTOR_PRESETS[$state] ?? SettingsService::SECTOR_PRESETS['other'];
                                            foreach ($preset as $key => $value) {
                                                $set($key, $value === '1' ? true : ($value === '0' ? false : $value));
                                            }
                                        })
                                        ->helperText('Choosing a sector applies default labels and feature presets. You can customise them below.'),

                                    TextInput::make('org_website')->label('Website')->url()->maxLength(255),
                                    TextInput::make('org_established_year')->label('Established Year')->numeric()->minValue(1800)->maxValue(2100),
                                    TextInput::make('org_address')->label('Address')->maxLength(500)->columnSpanFull(),
                                ])->columns(2),
                            ]),

                        Tabs\Tab::make('Terminology')
                            ->icon('heroicon-o-language')
                            ->schema([
                                Section::make('Custom Labels')
                                    ->description('Rename terms to match your organisation\'s language. These appear across the panel.')
                                    ->schema([
                                        TextInput::make('label_board')
                                            ->label('"Board" is called')
                                            ->placeholder('Board')
                                            ->maxLength(100),

                                        TextInput::make('label_meeting')
                                            ->label('"Meeting" is called')
                                            ->placeholder('Meeting')
                                            ->maxLength(100),

                                        TextInput::make('label_chairperson')
                                            ->label('"Chairperson" role is called')
                                            ->placeholder('Chairperson')
                                            ->maxLength(100),

                                        TextInput::make('label_secretary')
                                            ->label('"Board Secretary" role is called')
                                            ->placeholder('Board Secretary')
                                            ->maxLength(100),

                                        TextInput::make('label_member')
                                            ->label('"Board Member" role is called')
                                            ->placeholder('Board Member')
                                            ->maxLength(100),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Meetings')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Section::make('Defaults')->schema([
                                    Select::make('meeting_quorum_percent')
                                        ->label('Default Quorum (%)')
                                        ->options(['25' => '25%', '33' => '33%', '50' => '50%', '51' => '51%', '67' => '67% (Two-thirds)', '75' => '75%', '100' => '100% (Unanimous)'])
                                        ->required(),

                                    TextInput::make('meeting_notice_days')
                                        ->label('Minimum Notice Period (days)')
                                        ->numeric()->minValue(1)->maxValue(90)->required(),

                                    TextInput::make('meeting_default_duration')
                                        ->label('Default Duration (minutes)')
                                        ->numeric()->minValue(15)->maxValue(480),

                                    TextInput::make('meeting_max_guests')
                                        ->label('Max Guests per Meeting')
                                        ->numeric()->minValue(0)->maxValue(50),
                                ])->columns(2),

                                Section::make('Options')->schema([
                                    Toggle::make('meeting_allow_virtual')
                                        ->label('Allow Virtual / Online Meetings')
                                        ->inline(false),

                                    Toggle::make('meeting_rsvp_required')
                                        ->label('Require RSVP from Attendees')
                                        ->inline(false),
                                ])->columns(2),
                            ]),

                        Tabs\Tab::make('Agenda & Minutes')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Standard Fields')->schema([
                                    Toggle::make('agenda_show_decisions')
                                        ->label('Show "Decisions" field on agenda items')
                                        ->inline(false),

                                    Toggle::make('agenda_show_action')
                                        ->label('Show "Action By / Action Date" fields')
                                        ->inline(false),
                                ])->columns(2),

                                Section::make('Education / NAAC Fields')
                                    ->description('Enable these for colleges and universities that track NAAC compliance.')
                                    ->schema([
                                        Toggle::make('agenda_show_naac')
                                            ->label('Show NAAC Criteria No. field')
                                            ->inline(false),

                                        Toggle::make('agenda_show_hod_resolution')
                                            ->label('Show HOD Resolution No. & Date fields')
                                            ->inline(false),
                                    ])->columns(2),

                                Section::make('Custom Fields')
                                    ->description('Add up to 2 custom text fields to every agenda item.')
                                    ->schema([
                                        TextInput::make('agenda_custom_label_1')
                                            ->label('Custom Field 1 Label')
                                            ->placeholder('e.g. Reference No., File No., Department')
                                            ->maxLength(100),

                                        TextInput::make('agenda_custom_label_2')
                                            ->label('Custom Field 2 Label')
                                            ->placeholder('e.g. Budget Code, Committee')
                                            ->maxLength(100),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Features')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Section::make('Enable / Disable Modules')
                                    ->description('Disabled modules are hidden from navigation.')
                                    ->schema([
                                        Toggle::make('feature_committees')
                                            ->label('Committees')->inline(false),

                                        Toggle::make('feature_departments')
                                            ->label('Departments')->inline(false),

                                        Toggle::make('feature_circular_resolution')
                                            ->label('Circular Resolutions (email voting)')->inline(false),

                                        Toggle::make('feature_action_items')
                                            ->label('Action Items')->inline(false),

                                        Toggle::make('feature_board_packs')
                                            ->label('Board Packs')->inline(false),

                                        Toggle::make('feature_minutes')
                                            ->label('Minutes of Meeting')->inline(false),

                                        Toggle::make('feature_analytics')
                                            ->label('Analytics Dashboard')->inline(false),
                                    ])->columns(3),
                            ]),

                        Tabs\Tab::make('Notifications')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Section::make('Meeting Reminders')->schema([
                                    Toggle::make('notify_meeting_reminder')
                                        ->label('Send meeting reminders to attendees')
                                        ->inline(false)
                                        ->live(),

                                    TextInput::make('notify_reminder_days')
                                        ->label('Days before meeting to send reminder')
                                        ->numeric()->minValue(1)->maxValue(30),
                                ])->columns(2),

                                Section::make('Action Items')->schema([
                                    Toggle::make('notify_action_item_reminder')
                                        ->label('Send action item due-date reminders')
                                        ->inline(false)
                                        ->live(),

                                    TextInput::make('notify_action_due_days')
                                        ->label('Days before due date to remind')
                                        ->numeric()->minValue(1)->maxValue(14),
                                ])->columns(2),

                                Section::make('Other')->schema([
                                    Toggle::make('notify_minutes_on_approval')
                                        ->label('Distribute minutes automatically on approval')
                                        ->inline(false),

                                    Toggle::make('notify_circular_resolution')
                                        ->label('Notify voters when a circular resolution opens')
                                        ->inline(false),
                                ])->columns(2),
                            ]),

                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data    = $this->form->getState();
        $service = SettingsService::current();
        $service->saveAll($data);

        // Apply sector preset for labels if sector changed
        if (isset($data['org_sector'])) {
            $service->applySectorPreset($data['org_sector']);
            // Re-save whatever the user typed (overrides preset)
            $service->saveAll($data);
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
