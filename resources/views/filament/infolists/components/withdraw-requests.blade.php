<div class="space-y-3">
    @php
        $isRtl = app()->getLocale() === 'ar';
        $alignStart = $isRtl ? 'text-right' : 'text-left';
        $alignEnd = $isRtl ? 'text-left' : 'text-right';
        $currency = config('app.currency', 'SAR');
        $formatCurrency = function($amount) use ($isRtl, $currency) {
            $formatted = $amount;
            return $isRtl ? ($currency . ' ' . $formatted) : ($formatted . ' ' . $currency);
        };
    @endphp

    @if($getState() === null || $getState()->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2">{{ __('wallet.no_withdraw_requests_found') }}</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('wallet.amount') }}
                        </th>
                        <th class="px-4 py-3 {{ $alignStart }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('Status') }}
                        </th>
                        <th class="px-4 py-3 {{ $alignStart }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('wallet.notes') }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('Date') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($getState() as $request)
                        <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }}">
                                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/20 dark:text-blue-200">
                                    {{ $formatCurrency($request->amount) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($request->is_approved)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        {{ __('wallet.completed') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ __('wallet.pending') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="max-w-[28rem] truncate text-gray-900 dark:text-gray-100" title="{{ $request->notes ?? '' }}">
                                    {{ $request->notes ?? __('No notes') }}
                                </div>
                                @if($request->notes && strlen($request->notes) > 50)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('Click to view full notes') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }} text-gray-500 dark:text-gray-400">
                                {{ $request->created_at ? $request->created_at->format('Y-m-d H:i') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($getState()->count() >= 10)
            <div class="text-center py-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Showing latest 10 requests') }}
                </span>
            </div>
        @endif
    @endif
</div>
