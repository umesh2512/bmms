<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DepartmentResource\Pages;
use App\Models\Department;
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

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static ?string $navigationLabel = 'Departments';
    protected static string|\UnitEnum|null $navigationGroup = 'Organisation';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')->required()->maxLength(255),
                Textarea::make('description')->rows(3),
                Select::make('chairperson_id')
                    ->label('Chairperson')
                    ->options(fn () => User::withoutGlobalScope('tenant')
                        ->where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'active')
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
            ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('chairperson.name')->label('Chairperson')->searchable(),
                Tables\Columns\TextColumn::make('meetings_count')->label('Meetings')->counts('meetings'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit'   => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
