<?php

namespace App\Filament\Tenant\Resources\MeetingResource\Pages;

use App\Filament\Tenant\Resources\MeetingResource;
use App\Models\Meeting;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Meeting $record */
        $record = $this->getRecord();
        $actions = [Actions\DeleteAction::make()];

        foreach (Meeting::TRANSITIONS[$record->status] ?? [] as $next) {
            $actions[] = Actions\Action::make('transition_' . $next)
                ->label('→ ' . (Meeting::STATUS_LABELS[$next] ?? $next))
                ->color($next === 'archived' ? 'gray' : ($next === 'in_progress' ? 'success' : 'primary'))
                ->requiresConfirmation()
                ->action(fn () => $record->transitionTo($next))
                ->after(fn () => $this->refreshFormData(['status']));
        }

        return $actions;
    }
}
