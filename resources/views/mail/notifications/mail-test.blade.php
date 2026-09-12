<x-mail::message>
# Development mail test

This message confirms that {{ $appName }} can send mail using the **{{ $mailer }}** mailer.

SMTP host, port, username, and password are read from the environment. They are not stored in application code.

If you expected this in Mailpit or another inbox, check `MAIL_MAILER`, `MAIL_HOST`, and `MAIL_PORT` in `.env`.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
