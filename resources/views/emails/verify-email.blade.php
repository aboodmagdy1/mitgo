

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('Verify Email') }}</title>
    </head>
    <body>
        @php
        $query = parse_url($verificationUrl, PHP_URL_QUERY);
        parse_str($query, $params);
        $token = $params['token'];
        @endphp
        <h1>Verify Email</h1>
        <p>Thank you for registering with us. Please click the button below to verify your email address.</p>
        <a href="{{ $verificationUrl }}">Verify Email Address</a>
            @if(env('APP_ENV') == 'local')
            <p>{{ $token }}</p>
            @endif
        <p>If you did not create an account, no further action is required.</p>
        <p>This verification link will expire in :count minutes.</p>
        <p>Regards,<br>
        {{ env('APP_NAME') }} Team</p>

        
    </body>
    </html>