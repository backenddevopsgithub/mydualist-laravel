<x-dashboard.layout :user="$user" title="Help & Support - My Dua List">
    <main class="mx-auto max-w-3xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div>
            <h1 class="font-serif text-4xl font-bold tracking-tight text-emerald-950">Help & Support</h1>
            <p class="mt-3 text-sm leading-6 text-stone-600">Tell us what happened and we’ll review it carefully.</p>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.support.store') }}" enctype="multipart/form-data" class="mt-8 rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
            @csrf

            <div class="space-y-5">
                <div>
                    <label for="reason" class="block text-sm font-bold text-stone-900">Reason for Contact</label>
                    <select id="reason" name="reason" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        <option value="">Choose a reason</option>
                        @foreach ($reasons as $value => $label)
                            <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="block text-sm font-bold text-stone-900">First Name</label>
                        <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        @error('first_name') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="surname" class="block text-sm font-bold text-stone-900">Surname</label>
                        <input id="surname" name="surname" value="{{ old('surname', $user->last_name) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        @error('surname') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-bold text-stone-900">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                    @error('email') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="comments" class="block text-sm font-bold text-stone-900">Comments</label>
                    <textarea id="comments" name="comments" rows="6" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm leading-7 outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>{{ old('comments') }}</textarea>
                    @error('comments') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="image" class="block text-sm font-bold text-stone-900">Upload Image <span class="font-medium text-stone-400">(optional)</span></label>
                    <input id="image" name="image" type="file" accept="image/*" class="mt-2 block w-full rounded-2xl border border-dashed border-emerald-900/20 bg-emerald-50/50 px-4 py-4 text-sm font-semibold text-stone-700">
                    <p class="mt-2 text-xs font-semibold text-stone-500">PNG, JPG, GIF, or WebP up to 4MB.</p>
                    @error('image') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="mt-7 w-full rounded-2xl bg-emerald-900 px-5 py-4 text-sm font-extrabold text-white transition hover:bg-emerald-800">Send Support Request</button>
        </form>
    </main>
</x-dashboard.layout>
