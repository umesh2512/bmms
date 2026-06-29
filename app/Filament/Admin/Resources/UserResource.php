<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $navigationLabel = 'All Users';
    protected static ?int $navigationSort = 2;

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

                Select::make('tenant_id')
                    ->label('Organisation')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->nullable()
                    ->preload()
                    ->helperText('Leave blank for superadmin users.'),

                Select::make('status')
                    ->options([
                        'active'      => 'Active',
                        'invited'     => 'Invited',
                        'suspended'   => 'Suspended',
                        'deactivated' => 'Deactivated',
                    ])
                    ->required()
                    ->default('active'),
            ])->columns(2),

            Section::make('Role')->schema([
                Select::make('role')
                    ->label('Role')
                    ->options(
                        Role::all()->pluck('name', 'name')
                            ->mapWithKeys(fn ($name) => [
                                $name => ucwords(str_replace('_', ' ', $name)),
                            ])
                    )
                    ->required()
                    ->default('board_member')
                    ->dehydrated(false) // handled manually in afterCreate/afterSave
                    ->helperText('tenant_admin and board_secretary can log in to the manage panel.'),
            ]),

            Section::make($isCreate ? 'Password' : 'Change Password')
                ->description($isCreate ? null : 'Leave blank to keep the current password.')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required($isCreate)
                        ->minLength(8)
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->helperText($isCreate ? 'Min 8 characters.' : 'Leave blank to keep current password.'),

                    TextInput::make('password_confirmation')
                        ->password()
                        ->revealable()
                        ->required($isCreate)
                        ->same('password')
                        ->dehydrated(false)
                        ->label('Confirm Password'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withoutGlobalScope('tenant'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Organisation')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'      => 'Active',
                        'invited'     => 'Invited',
                        'suspended'   => 'Suspended',
                        'deactivated' => 'Deactivated',
                    ]),
                Tables\Filters\SelectFilter::make('tenant')
                    ->label('Organisation')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\EditAction::make(),

                Actions\Action::make('change_password')
                    ->label('Reset Password')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->form([
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('new_password'),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => Hash::make($data['new_password'])]);
                    })
                    ->successNotificationTitle('Password reset successfully'),

                Actions\Action::make('assign_org')
                    ->label('Assign Org')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->color('info')
                    ->form([
                        Select::make('tenant_id')
                            ->label('Organisation')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('role')
                            ->label('Role')
                            ->options(
                                Role::all()->pluck('name', 'name')
                                    ->mapWithKeys(fn ($name) => [
                                        $name => ucwords(str_replace('_', ' ', $name)),
                                    ])
                            )
                            ->required(),
                    ])
                    ->fillForm(fn (User $record) => [
                        'tenant_id' => $record->tenant_id,
                        'role'      => $record->getRoleNames()->first(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['tenant_id' => $data['tenant_id']]);
                        $record->syncRoles([$data['role']]);
                    })
                    ->successNotificationTitle('Organisation and role updated'),

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
                    ->visible(fn (User $record) => in_array($record->status, ['suspended', 'deactivated', 'invited']))
                    ->action(fn (User $record) => $record->update(['status' => 'active'])),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
