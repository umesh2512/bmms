<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\CommitteeResource\Pages;
use App\Models\Committee;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class CommitteeResource extends Resource
{
    protected static ?string $model = Committee::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static ?string $navigationLabel = 'Committees';
    protected static string|\UnitEnum|null $navigationGroup = 'Organisation';
    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        $tenantUsers = fn () => User::withoutGlobalScope('tenant')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('status', 'active')
            ->pluck('name', 'id');

        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->required()->maxLength(255),

                Select::make('type')
                    ->options([
                        'board'         => 'Board',
                        'audit'         => 'Audit',
                        'nomination'    => 'Nomination',
                        'remuneration'  => 'Remuneration',
                        'risk'          => 'Risk',
                        'other'         => 'Other',
                    ])
                    ->required()
                    ->default('other'),

                Textarea::make('description')->rows(3)->columnSpanFull(),

                Select::make('chairperson_id')
                    ->label('Chairperson')
                    ->options($tenantUsers)
                    ->searchable()
                    ->nullable(),

                Select::make('secretary_id')
                    ->label('Secretary')
                    ->options($tenantUsers)
                    ->searchable()
                    ->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('chairperson.name')->label('Chairperson')->searchable(),
                Tables\Columns\TextColumn::make('secretary.name')->label('Secretary')->searchable(),
                Tables\Columns\TextColumn::make('meetings_count')->label('Meetings')->counts('meetings'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['board' => 'Board', 'audit' => 'Audit', 'nomination' => 'Nomination', 'remuneration' => 'Remuneration', 'risk' => 'Risk', 'other' => 'Other']),
            ])
            ->actions([
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
            'index'  => Pages\ListCommittees::route('/'),
            'create' => Pages\CreateCommittee::route('/create'),
            'edit'   => Pages\EditCommittee::route('/{record}/edit'),
        ];
    }
}
