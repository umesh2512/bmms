<?php

namespace App\Filament\Tenant\Resources\MeetingResource\RelationManagers;

use App\Models\AgendaItem;
use App\Models\Resolution;
use App\Models\User;
use App\Services\ResolutionService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ResolutionsRelationManager extends RelationManager
{
    protected static string $relationship = 'resolutions';
    protected static ?string $title = 'Resolutions';

    public function form(Schema $schema): Schema
    {
        $meeting = $this->getOwnerRecord();

        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),

            Select::make('type')
                ->options(['ordinary' => 'Ordinary', 'special' => 'Special', 'circular' => 'Circular'])
                ->default('ordinary')->required(),

            Select::make('required_majority')
                ->options(Resolution::MAJORITY_LABELS)
                ->default('simple')->required(),

            Select::make('agenda_item_id')
                ->label('Agenda Item')
                ->options(fn () => AgendaItem::where('meeting_id', $meeting->id)
                    ->orderBy('order_column')
                    ->pluck('title', 'id'))
                ->nullable(),

            Select::make('proposed_by')
                ->label('Proposed By')
                ->options(fn () => User::withoutGlobalScope('tenant')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('status', 'active')
                    ->pluck('name', 'id'))
                ->default(auth()->id())
                ->searchable(),

            Select::make('seconded_by')
                ->label('Seconded By')
                ->options(fn () => User::withoutGlobalScope('tenant')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->where('status', 'active')
                    ->pluck('name', 'id'))
                ->searchable()
                ->nullable(),

            Toggle::make('is_secret_ballot')->label('Secret Ballot')->default(false),

            Textarea::make('body')->label('Resolution Text')->rows(4)->columnSpanFull(),
            Textarea::make('result_notes')->label('Notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(45)->searchable(),

                Tables\Columns\TextColumn::make('type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'special'  => 'warning',
                        'circular' => 'info',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'passed'   => 'success',
                        'failed'   => 'danger',
                        'voting'   => 'warning',
                        'proposed' => 'info',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('required_majority')
                    ->label('Majority')
                    ->formatStateUsing(fn (string $state): string => Resolution::MAJORITY_LABELS[$state] ?? $state),

                Tables\Columns\TextColumn::make('votes_yes')->label('Yes')->alignCenter(),
                Tables\Columns\TextColumn::make('votes_no')->label('No')->alignCenter(),
                Tables\Columns\TextColumn::make('votes_abstain')->label('Abs.')->alignCenter(),

                Tables\Columns\TextColumn::make('decided_at')->label('Decided')->date('d M Y')->placeholder('—'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Propose Resolution')
                    ->mutateFormDataBeforeCreate(fn (array $data) => array_merge($data, [
                        'tenant_id'  => auth()->user()->tenant_id,
                        'meeting_id' => $this->getOwnerRecord()->id,
                        'status'     => 'proposed',
                    ])),
            ])
            ->actions([
                Actions\Action::make('open_voting')
                    ->label('Open Voting')
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Resolution $r): bool => $r->status === 'proposed')
                    ->action(fn (Resolution $r) => app(ResolutionService::class)->openVoting($r)),

                Actions\Action::make('cast_vote')
                    ->label('Cast Vote')
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->color('primary')
                    ->visible(fn (Resolution $r): bool => $r->isOpen() && ! $r->hasVoted(auth()->id()))
                    ->form([
                        Select::make('vote')
                            ->options(['yes' => 'Yes — In Favour', 'no' => 'No — Against', 'abstain' => 'Abstain'])
                            ->required(),
                    ])
                    ->action(function (Resolution $r, array $data): void {
                        try {
                            app(ResolutionService::class)->castVote($r, auth()->user(), $data['vote']);
                            Notification::make()->title('Vote recorded')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),

                Actions\Action::make('close_voting')
                    ->label('Close & Decide')
                    ->icon(Heroicon::OutlinedStopCircle)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('This will close voting and record the result based on the current vote tally.')
                    ->visible(fn (Resolution $r): bool => $r->status === 'voting')
                    ->action(fn (Resolution $r) => app(ResolutionService::class)->closeVoting($r)),

                Actions\Action::make('view_votes')
                    ->label('View Votes')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->color('gray')
                    ->visible(fn (Resolution $r): bool => ! $r->is_secret_ballot && $r->totalVotes() > 0)
                    ->modalContent(fn (Resolution $r) => view('filament.resolutions.vote-list', [
                        'resolution' => $r->loadMissing('votes.user'),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Actions\Action::make('withdraw')
                    ->label('Withdraw')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Resolution $r): bool => in_array($r->status, ['proposed', 'voting']))
                    ->action(fn (Resolution $r) => $r->update(['status' => 'withdrawn'])),

                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->visible(fn (Resolution $r): bool => $r->status === 'proposed'),
            ])
            ->bulkActions([]);
    }
}
