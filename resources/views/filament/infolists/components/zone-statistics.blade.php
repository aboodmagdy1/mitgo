@php
    $stats = $zoneStats ?? [];
    $totalTrips = $stats['total_trips'] ?? 0;
    $completed = $stats['completed'] ?? 0;
    $completedPct = $stats['completed_percentage'] ?? null;
    $accepted = $stats['accepted'] ?? 0;
    $acceptancePct = $stats['acceptance_percentage'] ?? null;
    $rejected = $stats['rejected'] ?? 0;
    $rejectionPct = $stats['rejection_percentage'] ?? null;
    $cancelled = $stats['cancelled'] ?? 0;
    $cancellationPct = $stats['cancellation_percentage'] ?? null;
    $totalRequests = $stats['total_requests'] ?? 0;
    $isLoading = ($zoneStats ?? null) === null;
@endphp
<div wire:init="loadZoneStats" class="space-y-4">
    @if($isLoading)
        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 py-8">
            <x-filament::loading-indicator class="h-5 w-5" />
            <span class="text-sm">{{ 'جاري التحميل...' }}</span>
        </div>
    @else
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50/50 dark:bg-gray-800/50">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ 'إجمالي الرحلات المعينة' }}</h4>
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($totalTrips) }}</p>
        </div>

        @if($totalTrips > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ 'مكتملة' }}</span>
                    <p class="mt-1 text-lg font-semibold text-success-600 dark:text-success-400">
                        {{ $completedPct !== null ? $completedPct . '%' : '—' }}
                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">({{ number_format($completed) }})</span>
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ 'القبول' }}</span>
                    <p class="mt-1 text-lg font-semibold text-info-600 dark:text-info-400">
                        {{ $acceptancePct !== null ? $acceptancePct . '%' : '—' }}
                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">({{ number_format($accepted) }})</span>
                    </p>
                    @if($totalRequests > 0)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ str_replace(':total', number_format($totalRequests), 'من :total طلب مرسل') }}</p>
                    @endif
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ 'الرفض' }}</span>
                    <p class="mt-1 text-lg font-semibold text-warning-600 dark:text-warning-400">
                        {{ $rejectionPct !== null ? $rejectionPct . '%' : '—' }}
                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">({{ number_format($rejected) }})</span>
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ 'الإلغاء' }}</span>
                    <p class="mt-1 text-lg font-semibold text-danger-600 dark:text-danger-400">
                        {{ $cancellationPct !== null ? $cancellationPct . '%' : '—' }}
                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">({{ number_format($cancelled) }})</span>
                    </p>
                </div>
            </div>
        @else
            <div class="text-sm text-gray-600 dark:text-gray-400 py-4">
                {{ 'لم يتم تعيين رحلات لهذه المنطقة بعد.' }}
            </div>
        @endif
    @endif
</div>
