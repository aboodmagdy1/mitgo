<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('financial.daily_summary_title') }}
        </x-slot>

        <x-slot name="headerEnd">
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="exportExcel"
                    wire:loading.attr="disabled"
                    color="success"
                    size="sm"
                    icon="heroicon-m-table-cells"
                >
                    {{ __('financial.export_excel') }}
                </x-filament::button>

                <x-filament::button
                    wire:click="exportPdf"
                    wire:loading.attr="disabled"
                    color="danger"
                    size="sm"
                    icon="heroicon-m-document-arrow-down"
                >
                    {{ __('financial.export_pdf') }}
                </x-filament::button>
            </div>
        </x-slot>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        @foreach([
                            'date'               => __('financial.col_date'),
                            'total_trips'        => __('financial.col_total_trips'),
                            'total_revenue'      => __('financial.col_total_revenue'),
                            'commission'         => __('financial.col_commission'),
                            'driver_earnings'    => __('financial.col_driver_earnings'),
                            'coupon_discounts'   => __('financial.col_coupon_discounts'),
                            'cancellation_fees'  => __('financial.col_cancellation_fees'),
                            'waiting_fees'       => __('financial.col_waiting_fees'),
                            'net_revenue'        => __('financial.col_net_revenue'),
                        ] as $col => $label)
                        <th
                            wire:click="sortBy('{{ $col }}')"
                            class="cursor-pointer select-none px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        >
                            <span class="flex items-center gap-1">
                                {{ $label }}
                                @if($sortBy === $col)
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        @if($sortDir === 'asc')
                                            <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.832 6.29 12.77a.75.75 0 11-1.08-1.04l4.25-4.5a.75.75 0 011.08 0l4.25 4.5a.75.75 0 01-.02 1.06z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </span>
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                    @forelse($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $row->date }}</td>
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($row->total_trips) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-gray-100">SAR {{ number_format((float) $row->total_revenue, 2) }}</td>
                            <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400">SAR {{ number_format((float) $row->commission, 2) }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">SAR {{ number_format((float) $row->driver_earnings, 2) }}</td>
                            <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">SAR {{ number_format((float) $row->coupon_discounts, 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-500 dark:text-red-400">SAR {{ number_format((float) $row->cancellation_fees, 2) }}</td>
                            <td class="px-4 py-3 text-right text-orange-500 dark:text-orange-400">SAR {{ number_format((float) $row->waiting_fees, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400">SAR {{ number_format((float) $row->net_revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-gray-400 dark:text-gray-600">
                                {{ __('financial.no_data_for_period') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if($totalRows > 0)
                <tfoot class="bg-gray-100 dark:bg-gray-800/70 border-t-2 border-gray-300 dark:border-gray-600">
                    <tr>
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">{{ __('financial.total') }}</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ number_format($totals->total_trips) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">SAR {{ number_format($totals->total_revenue, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-blue-700 dark:text-blue-300">SAR {{ number_format($totals->commission, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-green-700 dark:text-green-300">SAR {{ number_format($totals->driver_earnings, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-amber-700 dark:text-amber-300">SAR {{ number_format($totals->coupon_discounts, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-red-700 dark:text-red-400">SAR {{ number_format($totals->cancellation_fees, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-orange-700 dark:text-orange-300">SAR {{ number_format($totals->waiting_fees, 2) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-700 dark:text-emerald-300">SAR {{ number_format($totals->net_revenue, 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination --}}
        @if($totalPages > 1)
        <div class="mt-4 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
            <p>
                {{ __('financial.showing_rows', [
                    'from' => (($currentPage - 1) * $perPage) + 1,
                    'to'   => min($currentPage * $perPage, $totalRows),
                    'total' => $totalRows,
                ]) }}
            </p>
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="prevPage"
                    :disabled="$currentPage <= 1"
                    size="sm"
                    color="gray"
                    icon="heroicon-m-chevron-left"
                >
                    {{ __('financial.prev') }}
                </x-filament::button>

                <span class="px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 font-medium">
                    {{ $currentPage }} / {{ $totalPages }}
                </span>

                <x-filament::button
                    wire:click="nextPage"
                    :disabled="$currentPage >= $totalPages"
                    size="sm"
                    color="gray"
                    icon-after="heroicon-m-chevron-right"
                >
                    {{ __('financial.next') }}
                </x-filament::button>
            </div>
        </div>
        @else
        <div class="mt-3 text-right text-xs text-gray-400 dark:text-gray-600">
            {{ __('financial.total_rows', ['total' => $totalRows]) }}
        </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
