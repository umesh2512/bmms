<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ActionItemResource\Pages;
use App\Models\ActionItem;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ActionItemResource extends Resource
{
    protected static ?string $model = ActionItem::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $navigationLabel = 'Action Items';
    protected static string|\UnitEnum|null $navigationGroup = 'Governance';
    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),

                Select::make('assigned_to')
                    ->label('Assigned To')
                    ->options(fn () => User::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')->pluck('name', 'id'))
                    ->searchable()->required(),

                DatePicker::make('due_date')->required(),

                Select::make('priority')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                    ->default('medium')->required(),

                Select::make('status')
                    ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'cancelled' => 'Cancelled'])
                    ->default('open')->required(),

                Textarea::make('description')->rows(3)->columnSpanFull(),
                Textarea::make('completion_notes')->label('Completion Notes')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(45)->searchable()->sortable(),

                Tables\Columns\TextColumn::make('meeting.title')->label('Meeting')->limit(25)->placeholder('—'),

                Tables\Columns\TextColumn::make('assignedTo.name')->label('Assigned To')->sortable(),

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
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'done' => 'Done', 'cancelled' => 'Cancelled']),

                Tables\Filters\SelectFilter::make('priority')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->options(fn () => User::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')->pluck('name', 'id')),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue only')
                    ->query(fn ($query) => $query->overdue()),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListActionItems::route('/'),
            'create' => Pages\CreateActionItem::route('/create'),
            'edit'   => Pages\EditActionItem::route('/{record}/edit'),
        ];
    }
}
