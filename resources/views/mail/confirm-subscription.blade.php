<x-mail::message>
# Confirm issue alerts

Please confirm that you want an email when {{ config('app.name') }} publishes a new issue. Alerts list published articles only.

<x-mail::button :url="$confirmUrl">
Confirm subscription
</x-mail::button>

If you did not request this, you can ignore the message.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
