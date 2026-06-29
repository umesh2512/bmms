<?php

namespace App\Filament\Tenant\Resources\DocumentResource\RelationManagers;

use App\Models\DocumentVersion;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';
    protected static ?string $title = 'Version History';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->defaultSort('version_number', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('version_number')
                    ->label('Version')
                    ->formatStateUsing(fn (int $state): string => "v{$state}")
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->getStateUsing(function (DocumentVersion $record): string {
                        $bytes = $record->file_size ?? 0;
                        if ($bytes < 1024) return "{$bytes} B";
                        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
                        return round($bytes / 1048576, 1) . ' MB';
                    }),

                Tables\Columns\TextColumn::make('change_note')
                    ->label('Change Note')
                    ->limit(50)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, g:i A'),
            ])
            ->headerActions([
                Actions\Action::make('upload_version')
                    ->label('Upload New Version')
                    ->icon(Heroicon::OutlinedArrowUpTray)
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
                            ->placeholder('Describe what changed in this version')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        $document  = $this->getOwnerRecord();
                        $filePath  = $data['file_path'];
                        $fileSize  = Storage::disk('local')->size($filePath);
                        $newVersion = $document->current_version + 1;

                        DocumentVersion::create([
                            'document_id'    => $document->id,
                            'version_number' => $newVersion,
                            'file_path'      => $filePath,
                            'file_size'      => $fileSize,
                            'change_note'    => $data['change_note'] ?? null,
                            'uploaded_by'    => auth()->id(),
                        ]);

                        $document->update([
                            'file_path'       => $filePath,
                            'file_size'       => $fileSize,
                            'file_type'       => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
                            'current_version' => $newVersion,
                        ]);
                    }),
            ])
            ->actions([
                Actions\Action::make('download_version')
                    ->label('Download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->url(fn (DocumentVersion $record): string => route('documents.version.download', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }
}
