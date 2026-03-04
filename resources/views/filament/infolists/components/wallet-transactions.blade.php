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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            <p class="mt-2">{{ __('wallet.no_transactions_found') }}</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 {{ $alignStart }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('wallet.transaction_type') }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('wallet.amount') }}
                        </th>
                        <th class="px-4 py-3 {{ $alignStart }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('wallet.transaction_description') }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ __('wallet.transaction_date') }}
                        </th>
                       
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($getState() as $transaction)
                        @php
                            $isDeposit = $transaction->type === 'deposit';
                            $amountAbs = abs($transaction->amount / 100);
                        @endphp
                        <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($isDeposit)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8l-8-8-8 8" />
                                        </svg>
                                        {{ __('wallet.deposit') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m8 8l-8 8-8-8" />
                                        </svg>
                                        {{ __('wallet.withdraw') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }}">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $isDeposit ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-200' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-200' }}">
                                    {{ $isDeposit ? '+' : '−' }} {{ $formatCurrency($amountAbs) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="max-w-[28rem] truncate text-gray-900 dark:text-gray-100" title="{{ $transaction->meta['description'] ?? '' }}">
                                    {{ $transaction->meta['description'] ?? __('No description') }}
                                </div>
                                @if(!empty($transaction->meta['notes']))
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate max-w-[28rem]" title="{{ $transaction->meta['notes'] }}">
                                        {{ $transaction->meta['notes'] }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }} text-gray-500 dark:text-gray-400">
                                {{ $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i') : '-' }}
                            </td>
                           
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($getState()->count() >= 10)
            <div class="text-center py-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Showing latest 10 transactions') }}
                </span>
            </div>
        @endif
    @endif
</div>
