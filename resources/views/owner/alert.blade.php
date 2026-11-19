<x-public-layout :title="$alert->vehicle->name.': '.$alert->reasonLabel()">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-green-50 border border-green-200 text-green-800 p-3 text-sm">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-sm text-gray-500">{{ $alert->created_at->diffForHumans() }} · {{ $alert->vehicle->name }}</p>
        <h1 class="mt-1 text-2xl font-bold">{{ $alert->reasonEmoji() }} {{ $alert->reasonLabel() }}</h1>
        @if ($alert->message)
            <blockquote class="mt-3 rounded-xl bg-gray-50 border-l-4 border-amber-400 p-3 text-gray-800">“{{ $alert->message }}”</blockquote>
        @endif
        @if ($alert->reply)
            <p class="mt-4 text-sm text-green-700">✅ You replied: <strong>{{ $alert->replyLabel() }}</strong></p>
        @endif
    </div>

    <h2 class="mt-6 mb-2 font-semibold">Reply to the person who scanned</h2>
    <div class="grid gap-2">
        @foreach ($replies as $key => $label)
            <form method="POST" action="{{ $alert->ownerReplyUrl($key) }}">
                @csrf
                <button class="w-full text-left rounded-2xl border px-4 py-4 font-medium transition
                    {{ $alert->reply === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white border-gray-200 hover:border-gray-400' }}">
                    {{ $label }}
                </button>
            </form>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-gray-500">They see your reply on their screen. Your contact details stay private.</p>

    <details class="mt-8 text-sm">
        <summary class="cursor-pointer text-gray-500">Not a real problem? Block this sender</summary>
        <form method="POST" action="{{ $blockUrl }}" class="mt-3">
            @csrf
            <button class="rounded-xl border border-red-300 text-red-700 px-4 py-2 hover:bg-red-50">Block alerts from this sender</button>
        </form>
    </details>

    @auth
        <a href="{{ route('dashboard') }}" class="mt-8 block text-center text-sm text-gray-600 underline">Back to dashboard</a>
    @endauth
</x-public-layout>
