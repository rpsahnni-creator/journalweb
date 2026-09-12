<x-mail::message>
# Reset your password

We received a request to reset the password for your account. This message does not contain your password.

<x-mail::button :url="$actionUrl">
Reset password
</x-mail::button>

This link expires in {{ $expireMinutes }} minutes. If you did not request a reset, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
