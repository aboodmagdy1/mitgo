<x-mail::message>
# {{ __('Reset Password') }}

{{ __('Your verification code is') }} {{ $resetCode }}.
{{ __('The code will expire in 15 minutes.') }}

{{ __('If you did not request a password reset, please ignore this email.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
