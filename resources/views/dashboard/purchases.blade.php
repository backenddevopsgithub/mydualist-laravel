<x-dashboard.layout :user="$user" title="Purchase History - My Dua List">
    <main class="mx-auto max-w-6xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <h1 class="dashboard-page-title">Purchase History</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600">Payments made through My Dua List, including upgrades and request packs.</p>

        <section class="mt-8 space-y-4">
            @forelse ($purchases as $purchase)
                @php
                    $statusLabel = Illuminate\Support\Str::headline($purchase->status->value);
                    if ($purchase->isRefunded()) {
                        $statusLabel = 'Refunded';
                    } elseif ($purchase->isDisputed()) {
                        $statusLabel = 'Disputed';
                    }
                @endphp
                <article class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_18px_60px_rgba(15,23,42,0.06)]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.14em] text-emerald-700">{{ $purchase->product?->name ?? 'Purchase' }}</p>
                            <p class="mt-2 text-lg font-extrabold text-stone-950">
                                £{{ number_format($purchase->amount_minor / 100, 2) }}
                                <span class="text-sm font-semibold uppercase text-stone-500">{{ $purchase->currency }}</span>
                            </p>
                            @if ($purchase->duaList)
                                <p class="mt-2 text-sm text-stone-600">List: {{ $purchase->duaList->title }}</p>
                            @endif
                        </div>
                        <span @class([
                            'rounded-full px-3 py-1 text-xs font-extrabold',
                            'bg-emerald-50 text-emerald-800' => $purchase->isCompleted() && ! $purchase->isRefunded() && ! $purchase->isDisputed(),
                            'bg-amber-50 text-amber-800' => ! $purchase->isCompleted() && ! $purchase->isRefunded() && ! $purchase->isDisputed(),
                            'bg-red-50 text-red-700' => $purchase->isRefunded() || $purchase->isDisputed() || $purchase->status->value === 'failed',
                        ])>{{ $statusLabel }}</span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs font-semibold text-stone-500">
                        <span>Purchased {{ $purchase->created_at->format('j M Y, H:i') }}</span>
                        @if ($purchase->fulfilled_at)
                            <span>Fulfilled {{ $purchase->fulfilled_at->format('j M Y, H:i') }}</span>
                        @endif
                        @if ($purchase->refunded_at)
                            <span>Refunded {{ $purchase->refunded_at->format('j M Y, H:i') }}</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-emerald-950/15 bg-white p-10 text-center shadow-sm">
                    <h2 class="text-2xl font-extrabold">No purchases yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">When you upgrade your plan or buy request packs, your receipts will appear here.</p>
                    <a href="{{ route('dashboard.upgrade') }}" class="mt-6 inline-flex rounded-full bg-emerald-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-950">View upgrade options</a>
                </div>
            @endforelse

            @if ($purchases->hasPages())
                <div class="mt-8">
                    {{ $purchases->links() }}
                </div>
            @endif
        </section>
    </main>
</x-dashboard.layout>
