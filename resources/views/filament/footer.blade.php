<div class="fi-footer py-4 text-center border-t border-gray-200 dark:border-gray-700" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        @if(app()->getLocale() === 'ar')
            <a href="https://linkdevelopment.sa/" target="_blank" style="color: #003c6e; text-decoration: none; font-weight: bold;">Link Development</a> {{ 'جميع الحقوق محفوظة' }} {{ date('Y') }} ©
        @else
            &copy; {{ date('Y') }} <a href="https://linkdevelopment.sa/" target="_blank" style="color: #003c6e; text-decoration: none; font-weight: bold;">Link Development</a>. {{ 'جميع الحقوق محفوظة' }}
        @endif
    </p>
</div>
