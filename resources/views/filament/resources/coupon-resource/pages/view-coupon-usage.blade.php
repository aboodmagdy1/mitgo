<x-filament-panels::page>
    <div class="space-y-6">
        <!-- معلومات الكوبون -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                معلومات الكوبون
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-sm text-blue-600 dark:text-blue-400">كود الكوبون</div>
                    <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ $this->record->code }}</div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-sm text-green-600 dark:text-green-400">نوع الخصم</div>
                    <div class="text-lg font-bold text-green-900 dark:text-green-100">
                        {{ $this->record->type == 1 ? 'نسبة مئوية' : 'مبلغ ثابت' }}
                    </div>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                    <div class="text-sm text-purple-600 dark:text-purple-400">قيمة الخصم</div>
                    <div class="text-lg font-bold text-purple-900 dark:text-purple-100">
                        {{ $this->record->type == 1 ? $this->record->amount . '%' : number_format($this->record->amount, 2) . ' ريال' }}
                    </div>
                </div>
                
                <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                    <div class="text-sm text-orange-600 dark:text-orange-400">عدد مرات الاستخدام</div>
                    <div class="text-lg font-bold text-orange-900 dark:text-orange-100">
                        {{ $this->record->used_count }}
                        @if($this->record->total_usage_limit)
                            / {{ $this->record->total_usage_limit }}
                        @endif
                    </div>
                </div>
            </div>

            @if($this->record->max_discount_amount && $this->record->type == 1)
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="text-sm text-yellow-600 dark:text-yellow-400">الحد الأقصى للخصم</div>
                    <div class="text-lg font-bold text-yellow-900 dark:text-yellow-100">
                        {{ number_format($this->record->max_discount_amount, 2) }} ريال
                    </div>
                </div>
            @endif

            @if($this->record->start_date || $this->record->end_date)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($this->record->start_date)
                        <div class="text-sm">
                            <span class="text-gray-600 dark:text-gray-400">تاريخ البداية:</span>
                            <span class="font-medium">{{ $this->record->start_date->format('Y-m-d') }}</span>
                        </div>
                    @endif
                    @if($this->record->end_date)
                        <div class="text-sm">
                            <span class="text-gray-600 dark:text-gray-400">تاريخ النهاية:</span>
                            <span class="font-medium">{{ $this->record->end_date->format('Y-m-d') }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- إحصائيات سريعة -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي المستخدمين</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $this->record->usages()->distinct('user_id')->count() }}
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي الخصم المطبق</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ number_format($this->record->usages()->sum('discount_amount'), 2) }} ريال
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">متوسط الخصم</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ number_format($this->record->usages()->avg('discount_amount'), 2) }} ريال
                </div>
            </div>
        </div>

        <!-- جدول تفاصيل الاستخدام -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    تفاصيل الاستخدام
                </h3>
            </div>
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
