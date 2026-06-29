<?php

namespace App\Filament\Tenant\Resources\MeetingResource\RelationManagers;

use App\Models\ActionItem;
use App\Models\AgendaItem;
use App\Models\Resolution;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ActionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'actionItems';
    protected static ?string $title = 'Action Items';

    public function form(Schema $schema): Schema
    {
        $meeting = $this->getOwnerRecord();

        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),

            Select::make('assigned_to')
                ->label('Assigned To')
                ->options(fn () => User::withoutGlobalScope('tenant')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('status', 'active')
                    ->pluck('name', 'id'))
                ->searchable()
                ->required(),

            DatePicker::make('due_date')->required(),

            Select::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                ->default('medium')->required(),

            Select::make('agenda_item_id')
                ->label('Agenda Item')
                ->options(fn () => AgendaItem::where('meeting_id', $meeting->id)
                    ->orderBy('order_column')
                    ->pluck('title', 'id'))
                ->nullable(),

            Select::make('resolution_id')
                ->label('Resolution')
                ->options(fn () => Resolution::withoutGlobalScope('tenant')
                    ->where('meeting_id', $meeting->id)
                    ->pluck('title', 'id'))
                ->nullable(),

            Select::make('status')
                ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'cancelled' => 'Cancelled'])
                ->default('open')->required(),

            Textarea::make('description')->rows(3)->columnSpanFull(),
            Textarea::make('completion_notes')->label('Completion Notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('due_date')
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(40)->searchable(),

                Tables\Columns\TextColumn::make('assignedTo.name')->label('Assigned To'),

                Tables\Columns\TextColumn::make('due_date')->date('d M Y')->sortable(),

                Tables\Columns\TextColumn::make('priority')->badge()
                    ->color(fn (string $state): string => ActionItem::PRIORITY_COLORS[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('display_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (ActionItem $r): string => $r->displayStatus())
                    ->color(fn (string $state): string => match ($state) {
                        'done'        => 'success',
                        'overdue'     => 'danger',
                        'in_progress' => 'warning',
                        'cancelled'   => 'gray',
                        default       => 'info',
                    }),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Add Action Item')
                    ->mutateFormDataBeforeCreate(fn (array $data) => array_merge($data, [
                        'tenant_id'   => auth()->user()->tenant_id,
                        'meeting_id'  => $this->getOwnerRecord()->id,
                        'assigned_by' => auth()->id(),
                    ])),
            ])
            ->actions([
                Actions\Action::make('mark_done')
                    ->label('Done')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (ActionItem $r): bool => ! in_array($r->status, ['done', 'cancelled']))
                    ->action(fn (ActionItem $r) => $r->update(['status' => 'done', 'completed_at' => now()])),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }
}
