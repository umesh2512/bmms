<?php

namespace App\Filament\Tenant\Resources\MeetingResource\RelationManagers;

use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AgendaRelationManager extends RelationManager
{
    protected static string $relationship = 'agendaItems';
    protected static ?string $title = 'Agenda';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),

            TextInput::make('order_column')
                ->label('Order')
                ->numeric()
                ->default(fn () => ($this->getOwnerRecord()->agendaItems()->max('order_column') ?? 0) + 1),

            TextInput::make('time_allocated')
                ->label('Time (minutes)')
                ->numeric()
                ->nullable(),

            Select::make('presenter_id')
                ->label('Presenter')
                ->options(fn () => User::withoutGlobalScope('tenant')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('status', 'active')
                    ->pluck('name', 'id'))
                ->searchable()
                ->nullable(),

            Toggle::make('resolution_required')->label('Resolution Required')->default(false),

            Textarea::make('description')->rows(3)->columnSpanFull(),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->columns([
                Tables\Columns\TextColumn::make('order_column')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(50),
                Tables\Columns\TextColumn::make('presenter.name')->label('Presenter'),
                Tables\Columns\TextColumn::make('time_allocated')->label('Mins'),
                Tables\Columns\IconColumn::make('resolution_required')->label('Resolution')->boolean(),
            ])
            ->headerActions([Actions\CreateAction::make()->label('Add Agenda Item')])
            ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
}
