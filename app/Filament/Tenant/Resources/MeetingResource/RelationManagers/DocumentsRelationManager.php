<?php

namespace App\Filament\Tenant\Resources\MeetingResource\RelationManagers;

use App\Models\AgendaItem;
use App\Models\Document;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'meetingDocuments';
    protected static ?string $title = 'Documents';

    public function form(Schema $schema): Schema
    {
        $meeting = $this->getOwnerRecord();

        return $schema->components([
            FileUpload::make('file')
                ->label('Upload Document')
                ->disk('local')
                ->directory('documents/' . auth()->user()->tenant_id)
                ->maxSize(51200)
                ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                ->columnSpanFull(),

            TextInput::make('document_name')->label('Document Name')->required()->maxLength(255),

            Select::make('agenda_item_id')
                ->label('Agenda Item')
                ->options(fn () => AgendaItem::where('meeting_id', $meeting->id)
                    ->orderBy('order_column')
                    ->pluck('title', 'id'))
                ->searchable()
                ->nullable(),

            Select::make('stage')
                ->options(['draft' => 'Draft', 'staged' => 'Staged', 'published' => 'Published'])
                ->default('draft')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document.name')
            ->defaultSort('order_column')
            ->columns([
                Tables\Columns\TextColumn::make('document.name')->label('Document')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('agendaItem.title')->label('Agenda Item')->limit(30),
                Tables\Columns\TextColumn::make('stage')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success', 'staged' => 'warning', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('document.file_size_for_humans')->label('Size'),
                Tables\Columns\TextColumn::make('document.uploader.name')->label('Uploaded By'),
            ])
            ->headerActions([
                Actions\Action::make('upload')
                    ->label('Upload Document')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowUpTray)
                    ->form([
                        FileUpload::make('file')
                            ->label('File')
                            ->disk('local')
                            ->directory('documents/' . (auth()->user()->tenant_id ?? 0))
                            ->maxSize(51200)
                            ->required(),
                        TextInput::make('name')->required()->maxLength(255),
                        Select::make('agenda_item_id')
                            ->label('Agenda Item')
                            ->options(fn () => AgendaItem::where('meeting_id', $this->getOwnerRecord()->id)
                                ->orderBy('order_column')
                                ->pluck('title', 'id'))
                            ->nullable(),
                        Select::make('stage')
                            ->options(['draft' => 'Draft', 'staged' => 'Staged', 'published' => 'Published'])
                            ->default('draft')->required(),
                    ])
                    ->action(function (array $data): void {
                        $meeting    = $this->getOwnerRecord();
                        $filePath   = $data['file'];
                        $fileSize   = Storage::disk('local')->size($filePath);
                        $extension  = pathinfo($filePath, PATHINFO_EXTENSION);

                        $document = Document::create([
                            'tenant_id'   => auth()->user()->tenant_id,
                            'name'        => $data['name'],
                            'file_path'   => $filePath,
                            'file_type'   => $extension,
                            'file_size'   => $fileSize,
                            'uploaded_by' => Auth::id(),
                        ]);

                        $meeting->meetingDocuments()->create([
                            'document_id'    => $document->id,
                            'agenda_item_id' => $data['agenda_item_id'] ?? null,
                            'stage'          => $data['stage'],
                            'order_column'   => $meeting->meetingDocuments()->count() + 1,
                        ]);
                    }),
            ])
            ->actions([
                Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn ($record) => $record->stage !== 'published')
                    ->action(fn ($record) => $record->update(['stage' => 'published'])),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
}
