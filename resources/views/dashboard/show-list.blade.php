@php
    $coverImageUrl = $duaList->coverImageUrl();
    $creatorName = trim(($duaList->user->first_name ?? '').' '.($duaList->user->last_name ?? '')) ?: $duaList->user->name;
    $completedCount = (int) ($duaList->completed_submissions_count ?? 0);
    $totalSubmissions = (int) ($duaList->submissions_count ?? 0);
    $progress = $totalSubmissions > 0 ? round(($completedCount / $totalSubmissions) * 100) : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Submit a dua request for {{ $duaList->title }} on My Dua List.">
        <title>{{ $duaList->title }} - My Dua List</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#fbfaf7] font-sans text-stone-950 antialiased">
        <main class="min-h-screen overflow-hidden">
            <section class="relative">
                <div class="absolute inset-x-0 top-0 h-80 bg-[radial-gradient(circle_at_20%_20%,rgba(16,185,129,0.16),transparent_30%),linear-gradient(135deg,#f7f2e7,#eef7ef)]"></div>

                <div class="relative mx-auto max-w-5xl px-5 py-6 sm:px-6 lg:px-8">
                    <header class="flex items-center justify-between">
                        <x-home.logo />
                        <a href="{{ route('home') }}" class="rounded-2xl bg-white/80 px-4 py-2 text-sm font-bold text-emerald-950 shadow-sm ring-1 ring-emerald-950/10">Home</a>
                    </header>

                    <article class="mx-auto mt-10 max-w-3xl overflow-hidden rounded-[2.25rem] border border-emerald-950/10 bg-white shadow-[0_30px_100px_rgba(15,23,42,0.10)]">
                        <div class="h-64 bg-emerald-50 sm:h-80">
                            @if ($coverImageUrl)
                                <img src="{{ $coverImageUrl }}" alt="{{ $duaList->title }} cover image" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_35%_20%,rgba(245,158,11,0.28),transparent_28%),linear-gradient(135deg,#064e3b,#f7f0dc)] text-white">
                                    <div class="text-center">
                                        <svg class="mx-auto h-16 w-16 opacity-90" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M7 5.5h10M7 10h10M7 14.5h6M5.5 3.5h13A1.5 1.5 0 0 1 20 5v14l-3-2-3 2-3-2-3 2-3-2V5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="mt-4 text-sm font-bold uppercase tracking-[0.18em] text-white/80">My Dua List</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="p-6 text-center sm:p-10">
                            <div class="flex flex-wrap justify-center gap-2">
                                <span class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.12em] text-emerald-800">{{ $duaList->occasionLabel() }}</span>
                                @if ($duaList->daysRemainingLabel())
                                    <span class="rounded-full bg-amber-50 px-4 py-2 text-xs font-extrabold text-amber-800">{{ $duaList->daysRemainingLabel() }}</span>
                                @endif
                            </div>

                            <h1 class="mt-6 font-serif text-4xl font-bold tracking-tight text-stone-950 sm:text-6xl">{{ $duaList->title }}</h1>
                            <p class="mx-auto mt-4 max-w-xl text-base leading-8 text-stone-600">
                                {{ $creatorName }} is collecting dua requests for {{ $duaList->occasionLabel() }}. Add your request and be part of something meaningful.
                            </p>

                            <div class="mx-auto mt-8 max-w-md">
                                <div class="flex items-center justify-between text-sm font-bold text-stone-600">
                                    <span>{{ $completedCount }} completed</span>
                                    <span>{{ $progress }}%</span>
                                    <span>{{ $totalSubmissions }} total</span>
                                </div>
                                <div class="mt-3 h-3 rounded-full bg-stone-100">
                                    <div class="h-3 rounded-full bg-emerald-700" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>

                            <div class="mt-9 grid gap-3 sm:grid-cols-2">
                                @if ($acceptsSubmissions)
                                    <a href="#submit-dua" class="inline-flex items-center justify-center rounded-2xl bg-emerald-900 px-6 py-4 text-sm font-extrabold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                                        Submit a Dua Request
                                    </a>
                                @else
                                    <div class="inline-flex items-center justify-center rounded-2xl bg-stone-100 px-6 py-4 text-sm font-extrabold text-stone-600">
                                        Submissions Closed
                                    </div>
                                @endif
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-emerald-950/10 bg-emerald-50 px-6 py-4 text-sm font-extrabold text-emerald-950 transition hover:bg-emerald-100"
                                    x-data="{ copied: false }"
                                    x-on:click="navigator.clipboard?.writeText(@js($duaList->publicUrl())); copied = true; setTimeout(() => copied = false, 1800)"
                                >
                                    <span x-show="! copied">Copy Share Link</span>
                                    <span x-cloak x-show="copied">Copied</span>
                                </button>
                            </div>
                        </div>
                    </article>

                    <section id="submit-dua" class="mx-auto mt-8 max-w-3xl rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_22px_70px_rgba(15,23,42,0.06)] sm:p-8">
                        @if (session('submission_status'))
                            <div class="mb-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                                {{ session('submission_status') }}
                            </div>
                        @endif

                        <h2 class="text-2xl font-extrabold tracking-tight text-stone-950">Submit a dua request</h2>

                        @if ($acceptsSubmissions)
                            <p class="mt-3 text-sm leading-6 text-stone-600">
                                Write the dua you would like {{ $creatorName }} to remember. Your name is optional, and you can submit anonymously.
                            </p>

                            <form
                                method="POST"
                                action="{{ route('dua-lists.submissions.store', $duaList) }}"
                                class="mt-7 space-y-5"
                                x-data="{
                                    maxDuas: 35,
                                    duas: @js(old('duas', old('content') ? [old('content')] : [''])),
                                    addDua() {
                                        if (this.duas.length < this.maxDuas) {
                                            this.duas.push('');
                                            this.$nextTick(() => this.$refs.duaBoxes?.querySelector('textarea:last-of-type')?.focus());
                                        }
                                    },
                                    removeDua(index) {
                                        if (this.duas.length > 1) {
                                            this.duas.splice(index, 1);
                                        }
                                    },
                                }"
                            >
                                @csrf

                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-emerald-50/70 p-4 text-sm font-semibold text-emerald-950 ring-1 ring-emerald-900/10">
                                    <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous')) class="mt-1 h-5 w-5 rounded border-emerald-200 text-emerald-800 focus:ring-emerald-700">
                                    Submit anonymously
                                </label>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="first_name" class="block text-sm font-bold text-stone-900">First Name <span class="font-medium text-stone-400">(optional)</span></label>
                                        <input id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="Your first name" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-stone-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                                        @error('first_name') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="last_name" class="block text-sm font-bold text-stone-900">Last Name <span class="font-medium text-stone-400">(optional)</span></label>
                                        <input id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Your last name" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-stone-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                                        @error('last_name') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-bold text-stone-900">Email <span class="font-medium text-stone-400">(optional, used for your submission limit)</span></label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-stone-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                                    @error('email') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <label class="block text-sm font-bold text-stone-900">Your dua requests</label>
                                            <p class="mt-1 text-xs font-semibold text-stone-500">Add up to 35 duas before submitting.</p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-800" x-text="`${duas.length}/${maxDuas}`"></span>
                                    </div>

                                    <div x-ref="duaBoxes" class="mt-3 space-y-3">
                                        <template x-for="(dua, index) in duas" :key="index">
                                            <div class="rounded-2xl border border-stone-200 bg-white p-3">
                                                <div class="mb-2 flex items-center justify-between gap-3">
                                                    <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-stone-500" x-text="`Dua ${index + 1}`"></p>
                                                    <button
                                                        type="button"
                                                        x-show="duas.length > 1"
                                                        x-on:click="removeDua(index)"
                                                        class="rounded-full bg-red-50 px-3 py-1 text-xs font-extrabold text-red-700"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                                <textarea
                                                    name="duas[]"
                                                    rows="4"
                                                    x-model="duas[index]"
                                                    placeholder="Write your dua here..."
                                                    class="block w-full rounded-xl border border-stone-100 bg-stone-50 px-4 py-3 text-sm leading-7 outline-none transition placeholder:text-stone-400 focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                                                    required
                                                ></textarea>
                                            </div>
                                        </template>
                                    </div>

                                    <button
                                        type="button"
                                        x-on:click="addDua"
                                        x-bind:disabled="duas.length >= maxDuas"
                                        class="mt-3 flex w-full items-center justify-center rounded-2xl border border-emerald-900/15 bg-emerald-50 px-5 py-3 text-sm font-extrabold text-emerald-950 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        + Add Another Dua
                                    </button>

                                    @error('duas') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    @error('duas.*') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    @error('content') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="note" class="block text-sm font-bold text-stone-900">Note <span class="font-medium text-stone-400">(optional)</span></label>
                                    <textarea id="note" name="note" rows="3" placeholder="Anything else you would like to share?" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm leading-7 outline-none transition placeholder:text-stone-400 focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">{{ old('note') }}</textarea>
                                    @error('note') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <button type="submit" class="w-full rounded-2xl bg-emerald-900 px-6 py-4 text-sm font-extrabold text-white shadow-sm shadow-emerald-950/20 transition hover:bg-emerald-800">
                                    Submit Dua Requests
                                </button>
                            </form>
                        @else
                            <div class="mt-6 rounded-2xl bg-stone-50 p-5 text-sm leading-7 text-stone-700 ring-1 ring-stone-200">
                                {{ $closedReason ?? 'This list is not accepting submissions right now.' }}
                            </div>
                            <a href="{{ route('onboarding.start') }}" class="mt-6 inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Create Your Own List</a>
                        @endif
                    </section>
                </div>
            </section>
        </main>
    </body>
</html>
