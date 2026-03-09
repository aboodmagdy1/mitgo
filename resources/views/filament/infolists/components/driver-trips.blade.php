<div class="w-full space-y-3">
    @php
        $isRtl = app()->getLocale() === 'ar';
        $alignStart = $isRtl ? 'text-right' : 'text-left';
        $alignEnd = $isRtl ? 'text-left' : 'text-right';
        $currency = config('app.currency', 'SAR');
    @endphp

    @if($getState() === null || $getState()->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p class="mt-2">{{ 'لا توجد رحلات' }}</p>
        </div>
    @else
        <div class="w-full overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="w-full min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 {{ $alignStart }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'الرحلة' }} #
                        </th>
                        <th class="px-4 py-3 {{ $alignStart }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'الحالة' }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'المسافة' }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'أرباح السائق' }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'التقييم' }}
                        </th>
                        <th class="px-4 py-3 {{ $alignStart }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'التعليق' }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'التاريخ' }}
                        </th>
                        <th class="px-4 py-3 {{ $alignEnd }} text-[11px] font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            {{ 'الإجراءات' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($getState() as $trip)
                        <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignStart }} font-medium text-gray-900 dark:text-gray-100">
                                {{ $trip->number ?? '#' . $trip->id }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @if(in_array($trip->status?->value ?? null, [\App\Enums\TripStatus::COMPLETED->value, \App\Enums\TripStatus::PAID->value]))
                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                    @elseif(in_array($trip->status?->value ?? null, [\App\Enums\TripStatus::CANCELLED_BY_DRIVER->value, \App\Enums\TripStatus::CANCELLED_BY_RIDER->value, \App\Enums\TripStatus::CANCELLED_BY_SYSTEM->value]))
                                        bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                    @else
                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                    @endif
                                ">
                                    {{ $trip->status?->label() ?? 'غير معروف' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }} text-gray-700 dark:text-gray-300">
                                {{ $trip->distance ? number_format((float) $trip->distance, 2) . ' km' : 'غير متاح' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }} text-gray-700 dark:text-gray-300">
                                @php
                                    $earning = $trip->payment?->driver_earning ?? null;
                                @endphp
                                @if($earning !== null)
                                    <span class="font-medium text-green-700 dark:text-green-300">
                                        {{ number_format((float) $earning, 2) }} {{ $currency }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">غير متاح</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }} text-gray-700 dark:text-gray-300">
                                @if($trip->rate)
                                    <span class="inline-flex items-center gap-0.5 font-medium">
                                        {{ $trip->rate->rating }} ★
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 {{ $alignStart }} text-gray-700 dark:text-gray-300 max-w-[200px]">
                                @if($trip->rate?->ratingComment)
                                    <span class="line-clamp-2" title="{{ $trip->rate->ratingComment->comment }}">
                                        {{ $trip->rate->ratingComment->comment }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }} text-gray-500 dark:text-gray-400">
                                {{ $trip->created_at?->format('Y-m-d H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap {{ $alignEnd }}">
                                <a href="{{ route('filament.admin.resources.trips.view', ['record' => $trip]) }}"
                                   class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20 transition-colors">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    {{ 'عرض' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($getState()->count() >= 15)
            <div class="text-center py-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ 'عرض آخر 15 رحلة' }}
                </span>
            </div>
        @endif
    @endif
</div>
