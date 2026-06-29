<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\MeetingResource\Pages;
use App\Filament\Tenant\Resources\MeetingResource\RelationManagers;
use App\Models\Committee;
use App\Models\Department;
use App\Models\Meeting;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static ?string $navigationLabel = 'Meetings';
    protected static string|\UnitEnum|null $navigationGroup = 'Meetings';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $tenantUsers = fn () => User::withoutGlobalScope('tenant')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'active')
            ->pluck('name', 'id');

        return $schema->components([
            Section::make('Meeting Details')->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),

                Select::make('type')
                    ->options(['board' => 'Board', 'agm' => 'AGM', 'egm' => 'EGM', 'committee' => 'Committee', 'department' => 'Department', 'other' => 'Other'])
                    ->required()
                    ->default('board'),

                Select::make('status')
                    ->options(Meeting::STATUS_LABELS)
                    ->required()
                    ->default('draft')
                    ->disabled(fn ($record) => $record !== null),

                Select::make('department_id')
                    ->label('Department')
                    ->options(fn () => Department::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                Select::make('committee_id')
                    ->label('Committee')
                    ->options(fn () => Committee::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
            ])->columns(2),

            Section::make('Schedule')->schema([
                DatePicker::make('scheduled_date')->label('Date')->required(),
                TimePicker::make('start_time')->label('Start Time'),
                TimePicker::make('end_time')->label('End Time'),
                TextInput::make('notice_days')->label('Notice Period (days)')->numeric()->default(21),
            ])->columns(2),

            Section::make('Location')->schema([
                TextInput::make('location')->maxLength(255)->placeholder('Physical address or venue name'),
                TextInput::make('online_link')->label('Online Link')->url()->placeholder('https://meet.google.com/...')->maxLength(500),
            ])->columns(2),

            Section::make('Chairperson & Secretary')->schema([
                Select::make('chairperson_id')->label('Chairperson')->options($tenantUsers)->searchable()->nullable(),
                Select::make('secretary_id')->label('Secretary')->options($tenantUsers)->searchable()->nullable(),
            ])->columns(2),

            Section::make('Quorum')->schema([
                Toggle::make('quorum_required')->label('Quorum Required')->default(true)->live(),
                TextInput::make('quorum_count')->label('Minimum Members')->numeric()->nullable()
                    ->visible(fn ($get) => $get('quorum_required')),
            ])->columns(2),

            Section::make('Notes')->schema([
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('scheduled_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'board' => 'primary', 'agm' => 'warning', 'egm' => 'danger',
                        'committee' => 'info', 'department' => 'success', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'                  => 'gray',
                        'scheduled'              => 'info',
                        'agenda_prepared'        => 'warning',
                        'board_pack_generated'   => 'warning',
                        'rsvp_active'            => 'primary',
                        'in_progress'            => 'success',
                        'minutes_drafted'        => 'warning',
                        'minutes_under_approval' => 'warning',
                        'closed'                 => 'success',
                        'archived'               => 'gray',
                        default                  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Meeting::STATUS_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('scheduled_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('location')->limit(30)->toggleable(),
                Tables\Columns\TextColumn::make('attendees_count')->label('Attendees')->counts('attendees'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['board' => 'Board', 'agm' => 'AGM', 'egm' => 'EGM', 'committee' => 'Committee', 'department' => 'Department', 'other' => 'Other']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(Meeting::STATUS_LABELS),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\AttendeesRelationManager::class,
            RelationManagers\GuestsRelationManager::class,
            RelationManagers\AgendaRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
            RelationManagers\BoardPacksRelationManager::class,
            RelationManagers\ResolutionsRelationManager::class,
            RelationManagers\ActionItemsRelationManager::class,
            RelationManagers\MinutesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'view'   => Pages\ViewMeeting::route('/{record}'),
            'edit'   => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
}
