<x-mail::message>
# {{ $alert->reasonEmoji() }} {{ $alert->reasonLabel() }}

Someone scanned the QR code on **{{ $alert->vehicle->name }}** just now.

@if ($alert->message)
<x-mail::panel>
“{{ $alert->message }}”
</x-mail::panel>
@endif

Let them know you're coming. They'll see your reply on their screen:

<x-mail::button :url="$alert->ownerUrl()" color="success">
Reply now
</x-mail::button>

Your email address is never shown to the person who scanned.

<small>Don't want these emails? Change your alert settings in your dashboard: {{ route('dashboard') }}</small>
</x-mail::message>
