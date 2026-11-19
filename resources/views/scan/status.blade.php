<x-public-layout title="Alert sent">
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center"
         x-data="alertStatus(@js(route('scan.poll', $alert->public_token)), @js([
             'seen' => (bool) $alert->seen_at,
             'reply' => $alert->reply,
             'reply_label' => $alert->replyLabel(),
             'replied_ago' => $alert->replied_at?->diffForHumans(),
         ]))">

        <template x-if="!s.reply">
            <div>
                <div class="mx-auto w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center text-3xl">📨</div>
                <h1 class="mt-4 text-2xl font-bold">Owner notified</h1>
                <p class="mt-2 text-gray-600">
                    We sent your alert about <strong>{{ $alert->vehicle->name }}</strong>.
                    Keep this page open: the owner's reply will show up here.
                </p>
                <div class="mt-6 flex items-center justify-center gap-2 text-sm">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                    <span x-text="s.seen ? 'The owner has seen your alert' : 'Waiting for the owner to see it…'"></span>
                </div>
            </div>
        </template>

        <template x-if="s.reply">
            <div>
                <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center text-3xl"
                     :class="s.reply === 'cant' ? 'bg-red-100' : 'bg-green-100'"
                     x-text="s.reply === 'cant' ? '😕' : (s.reply === 'moved' ? '✅' : '🏃')"></div>
                <p class="mt-4 text-sm text-gray-500">The owner replied <span x-text="s.replied_ago"></span></p>
                <h1 class="mt-1 text-2xl font-bold" x-text="'“' + s.reply_label + '”'"></h1>
            </div>
        </template>
    </div>

    <p class="mt-6 text-center text-sm text-gray-500">
        Sent {{ $alert->created_at->diffForHumans() }} · {{ $alert->reasonEmoji() }} {{ $alert->reasonLabel() }}
    </p>

    <script>
        function alertStatus(url, initial) {
            return {
                s: initial,
                init() {
                    const tick = async () => {
                        if (this.s.reply === 'moved') return;
                        try {
                            const r = await fetch(url, { headers: { Accept: 'application/json' } });
                            if (r.ok) this.s = await r.json();
                        } catch (e) {}
                        setTimeout(tick, document.hidden ? 15000 : 4000);
                    };
                    setTimeout(tick, 3000);
                },
            };
        }
    </script>
</x-public-layout>
