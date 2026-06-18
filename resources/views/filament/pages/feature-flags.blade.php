<x-filament-panels::page>
    <div class="space-y-4">
        @forelse ($this->getFlags() as $flag)
            <x-filament::section>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium text-gray-950 dark:text-white">{{ $flag['label'] }}</p>
                        <p class="text-xs text-gray-500">Environment key: {{ $flag['source'] }}</p>
                    </div>
                    <x-filament::badge :color="$flag['enabled'] ? 'success' : 'gray'">
                        {{ $flag['enabled'] ? 'Enabled' : 'Disabled' }}
                    </x-filament::badge>
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p class="text-sm text-gray-500">No feature flags configured.</p>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
