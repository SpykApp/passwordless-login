@component('mail::message')
# {{ __('passwordless-login::messages.email_greeting') }}

{{ __('passwordless-login::messages.email_intro') }}

@component('mail::button', ['url' => $url])
{{ __('passwordless-login::messages.email_action') }}
@endcomponent

{{ __('passwordless-login::messages.email_expiry_notice', ['minutes' => $expiryMinutes]) }}

{{ __('passwordless-login::messages.email_outro') }}

{{ __('passwordless-login::messages.email_salutation') }},<br>
{{ config('app.name') }}
@endcomponent
