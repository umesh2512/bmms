<?php

namespace App\Filament\Tenant\Resources\UserResource\Pages;

use App\Filament\Tenant\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalHeading('Import Users from CSV')
                ->modalDescription('Upload a CSV file to bulk-enrol users. Rows with duplicate emails are skipped.')
                ->form([
                    Placeholder::make('sample_download')
                        ->label('')
                        ->content(new HtmlString(
                            '<a href="' . asset('samples/users-import-sample.csv') . '" download
                                class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-500 font-medium">
                                Download sample CSV
                            </a>'
                        )),

                    FileUpload::make('csv_file')
                        ->label('CSV File')
                        ->disk('local')
                        ->directory('csv-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required()
                        ->helperText('Columns: name, email, designation (optional), phone (optional), role (optional), password (optional). Role defaults to board_member. A random password is set if left blank.'),
                ])
                ->action(function (array $data): void {
                    $uploadedPath = $data['csv_file'];
                    $filePath     = Storage::disk('local')->path($uploadedPath);

                    if (! file_exists($filePath)) {
                        Notification::make()
                            ->title('File not found')
                            ->danger()
                            ->send();
                        return;
                    }

                    $tenantId = auth()->user()->tenant_id;
                    $created  = 0;
                    $skipped  = 0;
                    $errors   = [];

                    $handle = fopen($filePath, 'r');
                    $header = null;

                    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                        if ($header === null) {
                            $header = array_map('trim', $row);
                            continue;
                        }

                        if (count($row) !== count($header)) {
                            continue;
                        }

                        $fields = array_combine($header, array_map('trim', $row));

                        $email = strtolower($fields['email'] ?? '');
                        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Invalid email: " . ($fields['email'] ?? '(empty)');
                            continue;
                        }

                        if (User::withoutGlobalScope('tenant')->where('email', $email)->exists()) {
                            $skipped++;
                            continue;
                        }

                        $name = $fields['name'] ?? '';
                        if (empty($name)) {
                            $errors[] = "Missing name for email: {$email}";
                            continue;
                        }

                        $role     = in_array($fields['role'] ?? '', ['tenant_admin', 'board_secretary', 'board_member', 'guest'])
                            ? $fields['role']
                            : 'board_member';
                        $password = filled($fields['password'] ?? '') ? $fields['password'] : Str::random(12) . '!1';

                        $user = User::create([
                            'name'        => $name,
                            'email'       => $email,
                            'designation' => $fields['designation'] ?? null,
                            'phone'       => $fields['phone'] ?? null,
                            'password'    => Hash::make($password),
                            'tenant_id'   => $tenantId,
                            'status'      => 'active',
                        ]);

                        $user->syncRoles([$role]);
                        $created++;
                    }

                    fclose($handle);
                    Storage::disk('local')->delete($uploadedPath);

                    $message = "{$created} user(s) imported.";
                    if ($skipped)  $message .= " {$skipped} skipped (duplicate email).";
                    if ($errors)   $message .= ' ' . count($errors) . ' row(s) had errors.';

                    Notification::make()
                        ->title($created > 0 ? 'Import complete' : 'Nothing imported')
                        ->body($message)
                        ->color($created > 0 ? 'success' : 'warning')
                        ->send();
                }),
        ];
    }
}
