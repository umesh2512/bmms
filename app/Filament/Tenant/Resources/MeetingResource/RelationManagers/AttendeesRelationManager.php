<?php

namespace App\Filament\Tenant\Resources\MeetingResource\RelationManagers;

use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AttendeesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendees';
    protected static ?string $title = 'Attendees';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('User')
                ->options(fn () => User::withoutGlobalScope('tenant')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('status', 'active')
                    ->pluck('name', 'id'))
                ->searchable()
                ->required(),

            Select::make('role')
                ->options(['chair' => 'Chair', 'secretary' => 'Secretary', 'member' => 'Member', 'invitee' => 'Invitee'])
                ->required()
                ->default('member'),

            Select::make('rsvp_status')
                ->label('RSVP')
                ->options(['pending' => 'Pending', 'yes' => 'Yes', 'no' => 'No', 'maybe' => 'Maybe', 'excused' => 'Excused'])
                ->default('pending'),

            Select::make('attendance_status')
                ->label('Attendance')
                ->options(['pending' => 'Pending', 'present' => 'Present', 'absent' => 'Absent', 'remote' => 'Remote', 'excused' => 'Excused', 'late' => 'Late', 'left_early' => 'Left Early'])
                ->default('pending'),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.designation')->label('Designation'),
                Tables\Columns\TextColumn::make('role')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'chair' => 'primary', 'secretary' => 'info', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('rsvp_status')->label('RSVP')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yes' => 'success', 'no' => 'danger', 'maybe' => 'warning', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('attendance_status')->label('Attendance')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success', 'absent' => 'danger', 'remote' => 'info', default => 'gray',
                    }),
            ])
            ->headerActions([Actions\CreateAction::make()->label('Add Attendee')])
            ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
}
