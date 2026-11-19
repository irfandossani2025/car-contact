<x-public-layout :title="'Alert the owner of '.$vehicle->name">
    @if ($turnstileSiteKey)
        <x-slot name="head">
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        </x-slot>
    @endif

    @if (! $vehicle->is_active)
        <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
            <div class="text-4xl mb-3">⏸️</div>
            <h1 class="text-xl font-semibold">Alerts are paused</h1>
            <p class="mt-2 text-gray-600">The owner of this car has temporarily turned off alerts.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <p class="text-sm text-gray-500">You scanned</p>
            <h1 class="text-2xl font-bold">{{ $vehicle->name }}</h1>
            @if ($vehicle->color || $vehicle->plate)
                <p class="text-gray-600 mt-1">
                    {{ $vehicle->color }}@if ($vehicle->color && $vehicle->plate) · @endif
                    @if ($vehicle->plate)<span class="font-mono">{{ $vehicle->plate }}</span>@endif
                </p>
            @endif
            <p class="mt-3 text-sm text-gray-600">Send the owner a quick, anonymous alert. They'll be notified on their phone right away.</p>
        </div>

        <form method="POST" action="{{ route('scan.send', $vehicle->tag_code) }}" class="mt-4 space-y-4"
              x-data="{ reason: @js(old('reason', '')), sending: false }" @submit="sending = true">
            @csrf

            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 p-3 text-sm">{{ $errors->first() }}</div>
            @endif

            <fieldset class="bg-white rounded-2xl shadow-sm p-3">
                <legend class="sr-only">Reason</legend>
                <p class="px-2 pt-1 pb-2 font-medium">What's the problem?</p>
                <div class="grid gap-2">
                    @foreach ($reasons as $key => $r)
                        <label class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer transition"
                               :class="reason === '{{ $key }}' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 hover:border-gray-400'">
                            <input type="radio" name="reason" value="{{ $key }}" x-model="reason" class="sr-only" required>
                            <span class="text-xl" aria-hidden="true">{{ $r['emoji'] }}</span>
                            <span class="font-medium">{{ $r['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="bg-white rounded-2xl shadow-sm p-4">
                <label for="message" class="font-medium">Add a note <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="message" name="message" rows="2" maxlength="280"
                          class="mt-2 w-full rounded-xl border-gray-300 focus:border-gray-900 focus:ring-gray-900"
                          placeholder="e.g. I need to leave in 10 minutes">{{ old('message') }}</textarea>
            </div>

            {{-- Honeypot for bots --}}
            <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

            @if ($turnstileSiteKey)
                <div class="cf-turnstile flex justify-center" data-sitekey="{{ $turnstileSiteKey }}"></div>
            @endif

            <button type="submit" :disabled="!reason || sending"
                    class="w-full rounded-2xl bg-amber-400 text-gray-900 font-bold text-lg py-4 shadow-sm disabled:opacity-50 active:scale-[.99] transition">
                <span x-show="!sending">Send alert to owner</span>
                <span x-show="sending" x-cloak>Sending…</span>
            </button>
            <p class="text-center text-xs text-gray-500">Please be kind. Repeated or abusive alerts are blocked.</p>
        </form>
    @endif
</x-public-layout>
