<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Cashback campaign info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ 'معلومات حملة الكاش باك' }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 dark:text-blue-400">{{ 'اسم الحملة' }}</div>
                    <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ $this->record->name }}</div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-sm text-green-600 dark:text-green-400">{{ 'نوع الكاش باك' }}</div>
                    <div class="text-lg font-bold text-green-900 dark:text-green-100">
                        {{ $this->record->type == \App\Models\CashbackCampaign::TYPE_PERCENTAGE ? 'نسبة مئوية' : 'مبلغ ثابت' }}
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 dark:text-purple-400">{{ 'القيمة' }}</div>
                    <div class="text-lg font-bold text-purple-900 dark:text-purple-100">
                        @if($this->record->type == \App\Models\CashbackCampaign::TYPE_PERCENTAGE)
                            {{ $this->record->amount }}%
                        @else
                            {{ number_format($this->record->amount, 2) }} ريال
                        @endif
                    </div>
                </div>

                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <div class="text-sm text-orange-600 dark:text-orange-400">{{ 'عدد الرحلات التي حصلت على كاش باك' }}</div>
                    <div class="text-lg font-bold text-orange-900 dark:text-orange-100">
                        {{ $this->record->used_trips_global }}
                        @if($this->record->max_trips_global)
                            / {{ $this->record->max_trips_global }}
                        @endif
                    </div>
                </div>
            </div>

            @if($this->record->max_cashback_amount && $this->record->type == \App\Models\CashbackCampaign::TYPE_PERCENTAGE)
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="text-sm text-yellow-600 dark:text-yellow-400">{{ 'الحد الأقصى للكاش باك لكل رحلة (ريال)' }}</div>
                    <div class="text-lg font-bold text-yellow-900 dark:text-yellow-100">
                        {{ number_format($this->record->max_cashback_amount, 2) }} ريال
                    </div>
                </div>
            @endif

            @if($this->record->start_date || $this->record->end_date)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($this->record->start_date)
                        <div class="text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ 'تاريخ البداية' }}:</span>
                            <span class="font-medium">{{ $this->record->start_date->format('Y-m-d H:i') }}</span>
                        </div>
                    @endif
                    @if($this->record->end_date)
                        <div class="text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ 'تاريخ النهاية' }}:</span>
                            <span class="font-medium">{{ $this->record->end_date->format('Y-m-d H:i') }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Quick stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ 'إجمالي المستخدمين' }}</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $this->record->cashbackUsages()->distinct('user_id')->count() }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ 'إجمالي الكاش باك الموزع' }}</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ number_format($this->record->cashbackUsages()->sum('cashback_amount'), 2) }} ريال
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ 'متوسط الكاش باك لكل رحلة' }}</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ number_format($this->record->cashbackUsages()->avg('cashback_amount'), 2) }} ريال
                </div>
            </div>
        </div>

        <!-- Usage details table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ 'تفاصيل استخدام الكاش باك' }}
                </h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>

