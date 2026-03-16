<x-mail::message>
# Verify Your Email

Your verification code is:

<x-mail::panel>
<h1 style="text-align: center; letter-spacing: 8px; font-size: 32px;">{{ $code }}</h1>
</x-mail::panel>

This code will expire in 15 minutes.

If you didn't create an account on Listory, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
