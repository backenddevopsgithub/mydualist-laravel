@php
    $faqs = $post->displayFaqs();
@endphp

@if ($faqs !== [])
    <section class="mt-16 border-t border-emerald-950/10 pt-12" x-data="{ openIndex: 0 }">
        <h2 class="font-serif text-3xl font-bold text-stone-950">Frequently Asked Questions</h2>

        <div class="mt-6 space-y-3">
            @foreach ($faqs as $index => $faq)
                <div class="overflow-hidden rounded-2xl border border-emerald-950/10 bg-white">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-lg font-bold text-stone-950"
                        x-on:click="openIndex = openIndex === {{ $index }} ? -1 : {{ $index }}"
                        x-bind:aria-expanded="openIndex === {{ $index }}"
                    >
                        <span>{{ $faq['question'] }}</span>
                        <span class="text-emerald-800" x-text="openIndex === {{ $index }} ? '−' : '+'"></span>
                    </button>
                    <div
                        x-show="openIndex === {{ $index }}"
                        x-cloak
                        class="border-t border-emerald-950/5 px-5 py-4 text-base leading-7 text-stone-700"
                    >
                        {!! nl2br(e($faq['answer'])) !!}
                    </div>
                </div>
            @endforeach
        </div>

        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => collect($faqs)->map(fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($faq['answer']),
                    ],
                ])->values()->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    </section>
@endif
