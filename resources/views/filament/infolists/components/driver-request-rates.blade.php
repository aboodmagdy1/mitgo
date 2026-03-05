<div wire:init="loadDriverRequestRates" class="space-y-2">
    @if(($driverRequestRates ?? null) === null)
        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
            <x-filament::loading-indicator class="h-5 w-5" />
            <span class="text-sm">{{ __('Loading...') }}</span>
        </div>
    @else
        @php
            $rates = $driverRequestRates ?? [];
            $total = $rates['total'] ?? 0;
            $responded = $rates['responded'] ?? 0;
            $accepted = $rates['accepted'] ?? 0;
            $rejected = $rates['rejected'] ?? 0;
        @endphp
        @if($total === 0 || $responded === 0)
            <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('stats.zero_requests') }}
            </div>
        @else
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('stats.driver_acceptance_rate') }}</span>
                    <p class="text-sm font-semibold text-success-600 dark:text-success-400">
                        {{ $accepted }}/{{ $responded }} ({{ $rates['acceptance_rate'] ?? 0 }}%)
                    </p>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('stats.driver_rejection_rate') }}</span>
                    <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">
                        {{ $rejected }}/{{ $responded }} ({{ $rates['rejection_rate'] ?? 0 }}%)
                    </p>
                </div>
            </div>
        @endif
    @endif
</div>
