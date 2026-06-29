<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                <x-filament::icon icon="heroicon-o-building-office-2" class="w-6 h-6 text-primary-600" />
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Welcome, {{ $userName }}
                    @if($isNew) — let's get started @endif
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $orgName }}</p>

                @if($isNew)
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <a href="{{ route('filament.tenant.resources.users.create') }}"
                       class="flex items-center gap-2 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-primary-50 dark:hover:bg-primary-950 transition">
                        <x-filament::icon icon="heroicon-o-user-plus" class="w-5 h-5 text-primary-600" />
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white">Add Users</p>
                            <p class="text-xs text-gray-400">Enrol board members</p>
                        </div>
                    </a>

                    <a href="{{ route('filament.tenant.resources.meetings.create') }}"
                       class="flex items-center gap-2 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-primary-50 dark:hover:bg-primary-950 transition">
                        <x-filament::icon icon="heroicon-o-calendar-plus" class="w-5 h-5 text-primary-600" />
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white">Schedule Meeting</p>
                            <p class="text-xs text-gray-400">Create your first meeting</p>
                        </div>
                    </a>

                    <a href="{{ route('filament.tenant.resources.documents.create') }}"
                       class="flex items-center gap-2 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-primary-50 dark:hover:bg-primary-950 transition">
                        <x-filament::icon icon="heroicon-o-document-arrow-up" class="w-5 h-5 text-primary-600" />
                        <div>
                            <p class="text-sm font-medium text-gray-800 dark:text-white">Upload Document</p>
                            <p class="text-xs text-gray-400">Add your first document</p>
                        </div>
                    </a>
                </div>
                @else
                <p class="text-sm text-gray-500 mt-2">
                    {{ $meetingCount }} meeting{{ $meetingCount === 1 ? '' : 's' }} recorded.
                    See the <strong>Analytics</strong> page for full governance statistics.
                </p>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
