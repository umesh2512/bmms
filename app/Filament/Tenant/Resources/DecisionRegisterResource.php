<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DecisionRegisterResource\Pages;
use App\Models\Resolution;
use App\Models\User;
use App\Services\ResolutionService;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class DecisionRegisterResource extends Resource
{
    protected static ?string $model = Resolution::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static ?string $navigationLabel = 'Decision Register';
    protected static ?string $slug = 'decision-register';
    protected static string|\UnitEnum|null $navigationGroup = 'Governance';
    protected static ?int $navigationSort = 30;

    // Show all resolutions (not just passed) — secretaries need full view
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with(['meeting', 'proposedBy']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resolution Details')->schema([
                TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),

                Select::make('type')
                    ->options(['ordinary' => 'Ordinary', 'special' => 'Special', 'circular' => 'Circular'])
                    ->required()->default('circular'),

                Select::make('required_majority')
                    ->options(Resolution::MAJORITY_LABELS)
                    ->required()->default('simple'),

                Select::make('proposed_by')
                    ->label('Proposed By')
                    ->options(fn () => User::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')->pluck('name', 'id'))
                    ->default(auth()->id())->searchable()->required(),

                Select::make('seconded_by')
                    ->label('Seconded By')
                    ->options(fn () => User::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')->pluck('name', 'id'))
                    ->searchable()->nullable(),

                Toggle::make('is_secret_ballot')->label('Secret Ballot')->default(false),

                Textarea::make('body')->label('Resolution Text')->rows(4)->columnSpanFull(),
            ])->columns(2),

            Section::make('Circular Voting Window')
                ->description('Set a deadline for circular resolutions sent by email.')
                ->visible(fn ($get) => $get('type') === 'circular')
                ->schema([
                    DateTimePicker::make('voting_opens_at')->label('Voting Opens')->default(now()),
                    DateTimePicker::make('voting_closes_at')->label('Voting Closes'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(50)->searchable()->sortable(),

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

                Tables\Columns\TextColumn::make('meeting.title')->label('Meeting')->limit(30)->placeholder('Circular'),

                Tables\Columns\TextColumn::make('votes_yes')->label('✓')->alignCenter(),
                Tables\Columns\TextColumn::make('votes_no')->label('✗')->alignCenter(),
                Tables\Columns\TextColumn::make('votes_abstain')->label('—')->alignCenter(),

                Tables\Columns\TextColumn::make('decided_at')->label('Decided')->date('d M Y')->sortable()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Resolution::STATUS_LABELS),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['ordinary' => 'Ordinary', 'special' => 'Special', 'circular' => 'Circular']),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('New Circular Resolution')
                    ->using(function (array $data): Resolution {
                        $data['tenant_id']  = auth()->user()->tenant_id;
                        $data['status']     = 'proposed';
                        $data['proposed_by'] = $data['proposed_by'] ?? auth()->id();

                        $resolution = Resolution::create($data);

                        if ($resolution->type === 'circular') {
                            app(ResolutionService::class)->openVoting($resolution);
                            app(ResolutionService::class)->notifyCircularResolutionVoters($resolution);
                        }

                        return $resolution;
                    }),
            ])
            ->actions([
                Actions\Action::make('open_voting')
                    ->label('Open Voting')
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Resolution $r): bool => $r->status === 'proposed' && $r->type !== 'circular')
                    ->action(fn (Resolution $r) => app(ResolutionService::class)->openVoting($r)),

                Actions\Action::make('close_voting')
                    ->label('Close & Decide')
                    ->icon(Heroicon::OutlinedStopCircle)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Resolution $r): bool => $r->status === 'voting')
                    ->action(fn (Resolution $r) => app(ResolutionService::class)->closeVoting($r)),

                Actions\EditAction::make()
                    ->visible(fn (Resolution $r): bool => ! $r->isDecided()),

                Actions\DeleteAction::make()
                    ->visible(fn (Resolution $r): bool => $r->status === 'proposed'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDecisions::route('/'),
            'create' => Pages\CreateDecision::route('/create'),
            'edit'   => Pages\EditDecision::route('/{record}/edit'),
        ];
    }
}
