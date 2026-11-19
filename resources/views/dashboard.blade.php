<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My cars</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 p-3 text-sm">{{ session('status') }}</div>
            @endif
            @if ($errors->any() && ! $errors->hasAny(['phone', 'code']))
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 p-3 text-sm">{{ $errors->first() }}</div>
            @endif

            @if (session('welcome'))
                <div class="rounded-2xl bg-gray-900 text-white p-6">
                    <h3 class="text-lg font-semibold">Welcome, {{ $user->name }}! You're 3 steps from done:</h3>
                    <ol class="mt-3 space-y-1 text-gray-200 list-decimal list-inside">
                        <li><strong class="text-amber-300">Download &amp; print</strong> your QR sticker below.</li>
                        <li><strong class="text-amber-300">Stick it</strong> inside your windshield, facing out.</li>
                        <li><strong class="text-amber-300">Turn on alerts</strong> on your phone (and Telegram if you use it).</li>
                    </ol>
                </div>
            @endif

            {{-- ===== How you'll be alerted ===== --}}
            <section class="bg-white shadow-sm rounded-2xl p-6">
                <h3 class="text-lg font-semibold">How you'll be alerted</h3>
                <p class="text-sm text-gray-500">Turn on at least one instant option (phone, SMS or Telegram).</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {{-- Phone push --}}
                    <div class="rounded-xl border p-4" x-data="pushManager(@js(config('contactscan.vapid.public_key')), @js(route('push.subscribe')), @js(route('push.unsubscribe')))">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium">📱 Phone notifications</h4>
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $pushCount ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $pushCount ? $pushCount.' device'.($pushCount > 1 ? 's' : '') : 'Off' }}
                            </span>
                        </div>

                        @if (! $pushEnabled)
                            <p class="mt-2 text-sm text-gray-500">Not configured on the server yet.</p>
                        @else
                            <template x-if="state === 'ios-install'">
                                <div class="mt-2 text-sm text-gray-700 space-y-1">
                                    <p><strong>On iPhone:</strong> first add this site to your Home Screen.</p>
                                    <p>Tap <strong>Share</strong> <span aria-hidden="true">⎋</span> → <strong>Add to Home Screen</strong>, open it from there, then tap “Turn on” here.</p>
                                </div>
                            </template>
                            <template x-if="state === 'unsupported'">
                                <p class="mt-2 text-sm text-gray-500">This browser doesn't support notifications. Try Chrome on Android, or use Telegram.</p>
                            </template>
                            <template x-if="state === 'denied'">
                                <p class="mt-2 text-sm text-red-700">Notifications are blocked. Allow them in your browser's site settings, then reload.</p>
                            </template>
                            <template x-if="state === 'off' || state === 'on' || state === 'working'">
                                <div class="mt-3">
                                    <p class="text-sm text-gray-600" x-text="state === 'on' ? 'This device will get alerts.' : 'Get alerts on this device, even when the site is closed.'"></p>
                                    <button type="button" @click="state === 'on' ? disable() : enable()" :disabled="state === 'working'"
                                            class="mt-3 w-full rounded-lg px-3 py-2 text-sm font-semibold"
                                            :class="state === 'on' ? 'border border-gray-300' : 'bg-gray-900 text-white'"
                                            x-text="state === 'working' ? 'Please wait…' : (state === 'on' ? 'Turn off on this device' : 'Turn on for this device')"></button>
                                </div>
                            </template>
                            <p class="mt-2 text-xs text-red-700" x-show="error" x-text="error"></p>

                            @if ($pushCount)
                                <div class="mt-3 flex gap-3 text-xs">
                                    <form method="POST" action="{{ route('push.test') }}">@csrf<button class="underline text-gray-700">Send test</button></form>
                                    <form method="POST" action="{{ route('push.removeAll') }}">@csrf<button class="underline text-gray-500">Remove all devices</button></form>
                                </div>
                            @endif
                        @endif
                    </div>

                    {{-- SMS --}}
                    <div class="rounded-xl border p-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium">💬 SMS text</h4>
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $user->canReceiveSms() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $user->canReceiveSms() ? 'On' : 'Off' }}
                            </span>
                        </div>
                        @if (! $smsEnabled)
                            <p class="mt-2 text-sm text-gray-500">Not configured on the server yet.</p>
                        @elseif ($user->phone_verified_at)
                            <p class="mt-3 text-sm text-gray-600">Texts go to <strong class="whitespace-nowrap">{{ $user->maskedPhone() }}</strong>. Works on any phone, no app needed.</p>
                            <div class="mt-3 grid gap-2">
                                <form method="POST" action="{{ route('sms.toggle') }}">@csrf
                                    <button class="w-full rounded-lg {{ $user->notify_sms ? 'border border-gray-300' : 'bg-gray-900 text-white' }} px-3 py-2 text-sm font-semibold">
                                        {{ $user->notify_sms ? 'Turn off SMS' : 'Turn on SMS' }}
                                    </button>
                                </form>
                            </div>
                            <div class="mt-3 flex gap-3 text-xs">
                                @if ($user->notify_sms)
                                    <form method="POST" action="{{ route('sms.test') }}">@csrf<button class="underline text-gray-700">Send test</button></form>
                                @endif
                                <form method="POST" action="{{ route('sms.remove') }}">@csrf<button class="underline text-gray-500">Change number</button></form>
                            </div>
                        @elseif ($user->phone && $user->phone_verify_expires_at?->isFuture())
                            <p class="mt-3 text-sm text-gray-600">Enter the 6-digit code we texted to <strong class="whitespace-nowrap">{{ $user->maskedPhone() }}</strong>.</p>
                            <form method="POST" action="{{ route('sms.verify') }}" class="mt-3 flex gap-2">
                                @csrf
                                <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="\d{6}" required placeholder="123456"
                                       class="w-full rounded-lg border-gray-300 text-center tracking-widest focus:border-gray-900 focus:ring-gray-900">
                                <button class="rounded-lg bg-gray-900 text-white px-3 text-sm font-semibold">Verify</button>
                            </form>
                            @error('code')<p class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror
                            <form method="POST" action="{{ route('sms.remove') }}" class="mt-2">@csrf<button class="text-xs underline text-gray-500">Use a different number</button></form>
                        @else
                            <p class="mt-3 text-sm text-gray-600">Get a text message on any phone. We'll send a code to confirm the number.</p>
                            <form method="POST" action="{{ route('sms.phone') }}" class="mt-3 grid gap-2">
                                @csrf
                                <input name="phone" type="tel" inputmode="tel" autocomplete="tel" required value="{{ old('phone') }}" placeholder="+971 50 123 4567"
                                       class="w-full rounded-lg border-gray-300 focus:border-gray-900 focus:ring-gray-900">
                                <button class="w-full rounded-lg bg-gray-900 text-white px-3 py-2 text-sm font-semibold">Send code</button>
                            </form>
                            @error('phone')<p class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror
                            <p class="mt-2 text-xs text-gray-500">Your number is never shown to the person who scans.</p>
                        @endif
                    </div>

                    {{-- Telegram --}}
                    <div class="rounded-xl border p-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium">✈️ Telegram</h4>
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $user->hasTelegram() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $user->hasTelegram() ? 'Connected' : 'Off' }}
                            </span>
                        </div>
                        @if (! $telegramEnabled)
                            <p class="mt-2 text-sm text-gray-500">Not configured on the server yet.</p>
                        @elseif ($user->hasTelegram())
                            <p class="mt-3 text-sm text-gray-600">Alerts arrive in Telegram with one-tap reply buttons.</p>
                            <form method="POST" action="{{ route('telegram.disconnect') }}" class="mt-3">
                                @csrf
                                <button class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold">Disconnect</button>
                            </form>
                        @else
                            <p class="mt-3 text-sm text-gray-600">Best option for iPhone. Opens Telegram. Just tap <strong>Start</strong>.</p>
                            <a href="{{ route('telegram.connect') }}" target="_blank" rel="noopener"
                               class="mt-3 block text-center w-full rounded-lg bg-sky-500 text-white px-3 py-2 text-sm font-semibold">Connect Telegram</a>
                            <p class="mt-2 text-xs text-gray-500">After tapping Start, reload this page.</p>
                        @endif
                    </div>

                    {{-- Email --}}
                    <div class="rounded-xl border p-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium">✉️ Email</h4>
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $user->notify_email ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $user->notify_email ? 'On' : 'Backup only' }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-gray-600">Sent to <strong class="break-all">{{ $user->email }}</strong>. If nothing else reaches you, we always email you.</p>
                        <form method="POST" action="{{ route('email.update') }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="notify_email" value="{{ $user->notify_email ? 0 : 1 }}">
                            <button class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold">
                                {{ $user->notify_email ? 'Only email when nothing else works' : 'Email me every alert' }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            {{-- ===== Cars ===== --}}
            @foreach ($vehicles as $vehicle)
                <section class="bg-white shadow-sm rounded-2xl p-6" x-data="{ editing: false }">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="shrink-0 mx-auto md:mx-0 text-center">
                            <div class="w-44 h-44 {{ $vehicle->is_active ? '' : 'opacity-30' }} [&>svg]:w-full [&>svg]:h-full">{!! $qrSvgs[$vehicle->id] !!}</div>
                            <p class="mt-1 font-mono text-sm tracking-widest text-gray-600">{{ $vehicle->displayCode() }}</p>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-xl font-semibold">{{ $vehicle->name }}</h3>
                                    <p class="text-sm text-gray-500">
                                        {{ collect([$vehicle->color, $vehicle->plate])->filter()->join(' · ') ?: 'No details added' }}
                                    </p>
                                </div>
                                <span class="text-xs rounded-full px-2 py-0.5 {{ $vehicle->is_active ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $vehicle->is_active ? 'Active' : 'Paused' }}
                                </span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('vehicles.sticker', $vehicle) }}" class="rounded-lg bg-amber-400 text-gray-900 px-4 py-2 text-sm font-bold">⬇ Download printable sticker (PDF)</a>
                                <a href="{{ $vehicle->scanUrl() }}" target="_blank" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Preview scan page</a>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                                <button type="button" @click="editing = !editing" class="underline text-gray-700">Edit details</button>
                                <form method="POST" action="{{ route('vehicles.toggle', $vehicle) }}">@csrf
                                    <button class="underline text-gray-700">{{ $vehicle->is_active ? 'Pause alerts' : 'Resume alerts' }}</button>
                                </form>
                                <form method="POST" action="{{ route('vehicles.regenerate', $vehicle) }}" onsubmit="return confirm('Create a new QR code? Your current printed sticker will stop working.')">@csrf
                                    <button class="underline text-gray-700">New QR code</button>
                                </form>
                                <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Remove {{ addslashes($vehicle->name) }} and its alerts?')">@csrf @method('DELETE')
                                    <button class="underline text-red-600">Remove</button>
                                </form>
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('vehicles.update', $vehicle) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
                                @csrf @method('PATCH')
                                <x-text-input name="name" :value="$vehicle->name" required maxlength="60" placeholder="Name" />
                                <x-text-input name="color" :value="$vehicle->color" maxlength="30" placeholder="Colour (optional)" />
                                <x-text-input name="plate" :value="$vehicle->plate" maxlength="20" placeholder="Plate (optional)" />
                                <div class="sm:col-span-3"><x-primary-button>Save</x-primary-button></div>
                            </form>

                            <p class="mt-4 text-xs text-gray-500">Plate and colour are shown to the person scanning so they know they have the right car. Leave blank to keep them private.</p>
                        </div>
                    </div>
                </section>
            @endforeach

            {{-- ===== Add a car ===== --}}
            <section class="bg-white shadow-sm rounded-2xl p-6">
                <h3 class="text-lg font-semibold">Add another car</h3>
                <form method="POST" action="{{ route('vehicles.store') }}" class="mt-3 grid gap-3 sm:grid-cols-4">
                    @csrf
                    <x-text-input name="name" required maxlength="60" placeholder="e.g. Blue Toyota RAV4" class="sm:col-span-2" />
                    <x-text-input name="color" maxlength="30" placeholder="Colour (optional)" />
                    <x-text-input name="plate" maxlength="20" placeholder="Plate (optional)" />
                    <div class="sm:col-span-4"><x-primary-button>Add car</x-primary-button></div>
                </form>
            </section>

            {{-- ===== Recent alerts ===== --}}
            <section class="bg-white shadow-sm rounded-2xl p-6">
                <h3 class="text-lg font-semibold">Recent alerts</h3>
                @forelse ($alerts as $alert)
                    <a href="{{ $alert->ownerUrl() }}" class="mt-3 flex items-center justify-between gap-3 rounded-xl border p-3 hover:border-gray-400">
                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ $alert->reasonEmoji() }} {{ $alert->reasonLabel() }} <span class="text-gray-500 font-normal">· {{ $alert->vehicle->name }}</span></p>
                            @if ($alert->message)<p class="text-sm text-gray-600 truncate">“{{ $alert->message }}”</p>@endif
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs text-gray-500">{{ $alert->created_at->diffForHumans() }}</p>
                            <p class="text-xs {{ $alert->reply ? 'text-green-700' : 'text-amber-700' }}">{{ $alert->reply ? 'Replied' : 'No reply' }}</p>
                        </div>
                    </a>
                @empty
                    <p class="mt-2 text-sm text-gray-500">No alerts yet. When someone scans your sticker, it shows up here.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
