<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $navigationLabel = 'Users';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $isCreate = $schema->getOperation() === 'create';

        return $schema->components([
            Section::make('User Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('designation')
                    ->maxLength(255)
                    ->placeholder('e.g. Chairperson, Board Secretary'),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),

                Select::make('status')
                    ->options([
                        'active'      => 'Active',
                        'suspended'   => 'Suspended',
                        'deactivated' => 'Deactivated',
                    ])
                    ->required()
                    ->default('active'),

                Select::make('role')
                    ->label('Role')
                    ->options(fn () => Role::whereNotIn('name', ['superadmin'])
                        ->pluck('name', 'name')
                        ->mapWithKeys(fn ($v, $k) => [$k => ucwords(str_replace('_', ' ', $v))]))
                    ->required()
                    ->default('board_member')
                    ->dehydrated(false)
                    ->helperText('tenant_admin and board_secretary can log in to this panel.'),
            ])->columns(2),

            Section::make($isCreate ? 'Set Password' : 'Change Password')
                ->description($isCreate ? null : 'Leave blank to keep the current password.')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required($isCreate)
                        ->minLength(8)
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),

                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->label('Confirm Password')
                        ->required($isCreate)
                        ->same('password')
                        ->dehydrated(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('designation')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'      => 'success',
                        'invited'     => 'warning',
                        'suspended'   => 'danger',
                        'deactivated' => 'gray',
                        default       => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y, g:i A')
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'      => 'Active',
                        'suspended'   => 'Suspended',
                        'deactivated' => 'Deactivated',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),

                Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->form([
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()->revealable()->required()->minLength(8),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm Password')
                            ->password()->revealable()->required()->same('new_password'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update(['password' => Hash::make($data['new_password'])]);
                    })
                    ->successNotificationTitle('Password reset successfully'),

                Actions\Action::make('change_role')
                    ->label('Change Role')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('info')
                    ->form([
                        Select::make('role')
                            ->label('New Role')
                            ->options(fn () => Role::whereNotIn('name', ['superadmin'])
                                ->pluck('name', 'name')
                                ->mapWithKeys(fn ($v, $k) => [$k => ucwords(str_replace('_', ' ', $v))]))
                            ->required(),
                    ])
                    ->fillForm(fn (User $record) => ['role' => $record->getRoleNames()->first()])
                    ->action(fn (User $record, array $data) => $record->syncRoles([$data['role']]))
                    ->successNotificationTitle('Role updated'),

                Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->status === 'active')
                    ->action(fn (User $record) => $record->update(['status' => 'suspended'])),

                Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (User $record) => $record->status !== 'active')
                    ->action(fn (User $record) => $record->update(['status' => 'active'])),

                Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', Filament::auth()->user()?->tenant_id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
