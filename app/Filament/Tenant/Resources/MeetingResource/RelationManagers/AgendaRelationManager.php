<?php

namespace App\Filament\Tenant\Resources\MeetingResource\RelationManagers;

use App\Models\User;
use App\Services\SettingsService;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AgendaRelationManager extends RelationManager
{
    protected static string $relationship = 'agendaItems';
    protected static ?string $title = 'Agenda';

    public function form(Schema $schema): Schema
    {
        $s = SettingsService::current();

        $showNaac      = $s->bool('agenda_show_naac');
        $showHod       = $s->bool('agenda_show_hod_resolution');
        $showDecisions = $s->bool('agenda_show_decisions');
        $showAction    = $s->bool('agenda_show_action');
        $custom1       = $s->get('agenda_custom_label_1');
        $custom2       = $s->get('agenda_custom_label_2');

        $components = [];

        // Item details — always visible
        $itemFields = [
            TextInput::make('order_column')
                ->label('Item No.')
                ->numeric()
                ->required()
                ->default(fn () => ($this->getOwnerRecord()->agendaItems()->max('order_column') ?? 0) + 1)
                ->columnSpan(1),

            TextInput::make('title')
                ->label('Title')
                ->required()
                ->maxLength(255)
                ->columnSpan($showNaac ? 2 : 3),
        ];

        if ($showNaac) {
            $itemFields[] = TextInput::make('naac_criteria_no')
                ->label('NAAC Criteria No.')
                ->maxLength(50)
                ->placeholder('e.g. 5.3.1')
                ->columnSpan(1);
        }

        $components[] = Section::make('Item Details')->schema($itemFields)->columns(4);

        // HOD Resolution — education sector
        if ($showHod) {
            $components[] = Section::make('HOD Resolution')->schema([
                TextInput::make('hod_resolution_no')
                    ->label('HOD Resolution No.')
                    ->maxLength(100),
                DatePicker::make('hod_resolution_date')
                    ->label('Dated')
                    ->native(false),
            ])->columns(2);
        }

        // Points Noted — always visible
        $components[] = Section::make('Points Noted and Discussed')->schema([
            Textarea::make('points_discussed')
                ->label('')
                ->rows(6)
                ->columnSpanFull()
                ->placeholder("Describe the points noted and discussed.\n\nUse new lines to separate points."),
        ]);

        // Decisions
        if ($showDecisions) {
            $components[] = Section::make('Decisions')->schema([
                Textarea::make('decisions')
                    ->label('')
                    ->rows(4)
                    ->columnSpanFull()
                    ->placeholder('Record the formal decisions or resolutions taken.'),
            ]);
        }

        // Action By / Date
        if ($showAction) {
            $components[] = Section::make('Action')->schema([
                Select::make('action_by')
                    ->label('Action By')
                    ->options(fn () => User::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')
                        ->pluck('name', 'name'))
                    ->searchable()
                    ->nullable(),
                DatePicker::make('action_date')
                    ->label('Action Date')
                    ->native(false)
                    ->nullable(),
            ])->columns(2);
        }

        // Custom fields
        if ($custom1 || $custom2) {
            $customFields = [];
            if ($custom1) {
                $customFields[] = TextInput::make('agenda_custom_field_1')->label($custom1)->maxLength(255);
            }
            if ($custom2) {
                $customFields[] = TextInput::make('agenda_custom_field_2')->label($custom2)->maxLength(255);
            }
            $components[] = Section::make('Additional Fields')->schema($customFields)->columns(2);
        }

        // Presenter / Time — collapsed extras
        $components[] = Section::make('More Details')->schema([
            Select::make('presenter_id')
                ->label('Presenter')
                ->options(fn () => User::withoutGlobalScope('tenant')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('status', 'active')
                    ->pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            TextInput::make('time_allocated')
                ->label('Time Allocated (minutes)')
                ->numeric()
                ->nullable(),
        ])->columns(2)->collapsed();

        return $schema->components($components);
    }

    public function table(Table $table): Table
    {
        $s = SettingsService::current();

        $columns = [
            Tables\Columns\TextColumn::make('order_column')
                ->label('Item No.')
                ->width('80px')
                ->sortable(),

            Tables\Columns\TextColumn::make('title')
                ->label('Title')
                ->searchable()
                ->wrap(),

            Tables\Columns\TextColumn::make('points_discussed')
                ->label('Points Noted & Discussed')
                ->limit(80)
                ->wrap()
                ->placeholder('—'),
        ];

        if ($s->bool('agenda_show_decisions')) {
            $columns[] = Tables\Columns\TextColumn::make('decisions')
                ->label('Decisions')
                ->limit(60)
                ->wrap()
                ->placeholder('—');
        }

        if ($s->bool('agenda_show_naac')) {
            $columns[] = Tables\Columns\TextColumn::make('naac_criteria_no')
                ->label('NAAC Criteria')
                ->placeholder('—');
        }

        if ($s->bool('agenda_show_action')) {
            $columns[] = Tables\Columns\TextColumn::make('action_by')->label('Action By')->placeholder('—');
            $columns[] = Tables\Columns\TextColumn::make('action_date')->label('Action Date')->date('d M Y')->placeholder('—');
        }

        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->columns($columns)
            ->headerActions([
                Actions\CreateAction::make()->label('Add Agenda Item'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }
}
