<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DocumentResource\Pages;
use App\Filament\Tenant\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel = 'Documents';
    protected static string|\UnitEnum|null $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        $isCreate = $schema->getRecord() === null;

        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_confidential')
                    ->label('Confidential')
                    ->helperText('Confidential documents are visible only to board secretaries and tenant admins.')
                    ->default(false),
            ])->columns(2),

            Section::make('File')
                ->schema([
                    FileUpload::make('file_path')
                        ->label('Upload File')
                        ->disk('local')
                        ->directory(fn () => 'documents/' . (auth()->user()->tenant_id ?? 0))
                        ->maxSize(51200)
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'image/png',
                            'image/jpeg',
                        ])
                        ->required($isCreate)
                        ->columnSpanFull(),
                ])
                ->visible($isCreate),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(45),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'pdf'  => 'danger',
                        'docx', 'doc' => 'info',
                        'xlsx', 'xls' => 'success',
                        'pptx', 'ppt' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => strtoupper((string) $state)),

                Tables\Columns\TextColumn::make('file_size_for_humans')
                    ->label('Size')
                    ->getStateUsing(fn (Document $record): string => $record->file_size_for_humans),

                Tables\Columns\TextColumn::make('current_version')
                    ->label('Ver.')
                    ->formatStateUsing(fn (int $state): string => "v{$state}"),

                Tables\Columns\IconColumn::make('is_confidential')
                    ->label('Conf.')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedLockClosed)
                    ->falseIcon(Heroicon::OutlinedLockOpen)
                    ->trueColor('danger')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('file_type')
                    ->options(['pdf' => 'PDF', 'docx' => 'Word', 'xlsx' => 'Excel', 'pptx' => 'PowerPoint']),

                Tables\Filters\TernaryFilter::make('is_confidential')
                    ->label('Confidential'),
            ])
            ->actions([
                Actions\Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->url(fn (Document $record): string => route('documents.download', $record->id))
                    ->openUrlInNewTab(),

                Actions\Action::make('new_version')
                    ->label('New Version')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('warning')
                    ->form([
                        FileUpload::make('file_path')
                            ->label('New File')
                            ->disk('local')
                            ->directory(fn () => 'documents/' . (auth()->user()->tenant_id ?? 0))
                            ->maxSize(51200)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('change_note')
                            ->label('Change Note')
                            ->rows(2)
                            ->placeholder('What changed in this version?')
                            ->columnSpanFull(),
                    ])
                    ->action(function (Document $record, array $data): void {
                        $filePath = $data['file_path'];
                        $fileSize = \Illuminate\Support\Facades\Storage::disk('local')->size($filePath);
                        $newVersion = $record->current_version + 1;

                        \App\Models\DocumentVersion::create([
                            'document_id'    => $record->id,
                            'version_number' => $newVersion,
                            'file_path'      => $filePath,
                            'file_size'      => $fileSize,
                            'change_note'    => $data['change_note'] ?? null,
                            'uploaded_by'    => auth()->id(),
                        ]);

                        $record->update([
                            'file_path'       => $filePath,
                            'file_size'       => $fileSize,
                            'file_type'       => pathinfo($filePath, PATHINFO_EXTENSION),
                            'current_version' => $newVersion,
                        ]);
                    })
                    ->successNotificationTitle('New version uploaded'),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\VersionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit'   => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
