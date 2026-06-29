<?php

namespace App\Filament\Tenant\Resources\MeetingResource\RelationManagers;

use App\Models\Minutes;
use App\Services\MinutesService;
use Filament\Actions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class MinutesRelationManager extends RelationManager
{
    protected static string $relationship = 'minutes';
    protected static ?string $title = 'Minutes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Repeater::make('content')
                ->label('Agenda Item Minutes')
                ->schema([
                    Hidden::make('agenda_item_id'),
                    TextInput::make('title')
                        ->label('Agenda Item')
                        ->disabled()
                        ->dehydrated(false),
                    Textarea::make('content')
                        ->label('Minutes / Notes')
                        ->rows(4)
                        ->placeholder('Record what was discussed, decided, or noted for this item...')
                        ->columnSpanFull(),
                ])
                ->reorderable(false)
                ->addable(false)
                ->deletable(false)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $meeting = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'locked'       => 'success',
                        'approved'     => 'info',
                        'under_review' => 'warning',
                        default        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('draftedBy.name')->label('Drafted By'),
                Tables\Columns\TextColumn::make('approvedBy.name')->label('Approved By')->placeholder('—'),
                Tables\Columns\TextColumn::make('approved_at')->label('Approved')->date('d M Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('locked_at')->label('Locked')->date('d M Y')->placeholder('—'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Start Draft')
                    ->visible(fn (): bool => ! $meeting->minutes()->exists())
                    ->fillForm(function () use ($meeting): array {
                        $items = $meeting->agendaItems()
                            ->orderBy('order_column')
                            ->get()
                            ->map(fn ($item) => [
                                'agenda_item_id' => $item->id,
                                'title'          => $item->order_column . '. ' . $item->title,
                                'content'        => '',
                            ])
                            ->toArray();

                        // Add a general notes item
                        $items[] = ['agenda_item_id' => null, 'title' => 'General Notes', 'content' => ''];

                        return ['content' => $items];
                    })
                    ->mutateFormDataBeforeCreate(fn (array $data) => array_merge($data, [
                        'meeting_id' => $meeting->id,
                        'drafted_by' => auth()->id(),
                        'status'     => 'draft',
                    ])),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->label('Edit Draft')
                    ->visible(fn (Minutes $r): bool => ! $r->isLocked()),

                Actions\Action::make('submit_review')
                    ->label('Submit for Approval')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Minutes $r): bool => $r->status === 'draft')
                    ->action(function (Minutes $r): void {
                        app(MinutesService::class)->submitForReview($r);
                        Notification::make()->title('Minutes submitted for approval')->success()->send();
                    }),

                Actions\Action::make('approve')
                    ->label('Approve & Lock')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Approving will lock the minutes and generate a signed PDF. The meeting will advance to Closed.')
                    ->visible(fn (Minutes $r): bool => $r->status === 'under_review')
                    ->action(function (Minutes $r): void {
                        app(MinutesService::class)->approve($r, auth()->id());
                        Notification::make()->title('Minutes approved and locked. PDF generated.')->success()->send();
                    }),

                Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->url(fn (Minutes $r): string => route('minutes.download', $r->id))
                    ->openUrlInNewTab()
                    ->visible(fn (Minutes $r): bool => (bool) $r->file_path),
            ])
            ->bulkActions([]);
    }
}
