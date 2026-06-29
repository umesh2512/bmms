<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    protected static ?string $navigationLabel = 'Organisations';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Organisation Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) =>
                        $set('slug', Str::slug($state ?? ''))
                    ),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('Auto-generated from name. You can edit it.')
                    ->rules(['alpha_dash']),

                TextInput::make('contact_email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                TextInput::make('contact_phone')
                    ->tel()
                    ->maxLength(20),

                TextInput::make('gstin')
                    ->label('GSTIN')
                    ->maxLength(20),

                Textarea::make('address')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('timezone')
                    ->options(array_combine(
                        \DateTimeZone::listIdentifiers(),
                        \DateTimeZone::listIdentifiers()
                    ))
                    ->default('Asia/Kolkata')
                    ->searchable()
                    ->required(),
            ])->columns(2),

            Section::make('Plan & Status')->schema([
                Select::make('plan')
                    ->options([
                        'free'        => 'Free',
                        'sponsored'   => 'Sponsored',
                        'premium'     => 'Premium',
                        'enterprise'  => 'Enterprise',
                    ])
                    ->required()
                    ->default('free')
                    ->helperText('Free / Sponsored = no billing. Premium / Enterprise = paid.'),

                Select::make('status')
                    ->options([
                        'trial'     => 'Trial',
                        'active'    => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('active'),

                DateTimePicker::make('trial_ends_at')
                    ->label('Trial Ends At'),
            ])->columns(3),

            // ── Only shown on Create ──────────────────────────────────────
            Section::make('First Admin User')
                ->description('Create the organisation administrator account. They will be able to log in immediately.')
                ->schema([
                    TextInput::make('admin_name')
                        ->label('Admin Full Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('admin_email')
                        ->label('Admin Email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    TextInput::make('admin_password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8)
                        ->dehydrated(true)
                        ->helperText('Min 8 characters. The admin can change this after logging in.'),
                ])
                ->columns(3)
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contact_email')->searchable(),
                Tables\Columns\TextColumn::make('plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free'       => 'gray',
                        'sponsored'  => 'info',
                        'premium'    => 'success',
                        'enterprise' => 'warning',
                        default      => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'trial'     => 'warning',
                        'active'    => 'success',
                        'suspended' => 'danger',
                        'cancelled' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trial'     => 'Trial',
                        'active'    => 'Active',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'free'       => 'Free',
                        'sponsored'  => 'Sponsored',
                        'premium'    => 'Premium',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record) => $record->status === 'active')
                    ->action(fn (Tenant $record) => $record->update(['status' => 'suspended'])),
                Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Tenant $record) => $record->status !== 'active')
                    ->action(fn (Tenant $record) => $record->update(['status' => 'active'])),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit'   => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
