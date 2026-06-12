<x-dashboard.layout :user="$user" title="Help & Support - My Dua List">
    <main class="mx-auto max-w-3xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div>
            <h1 class="dashboard-page-title">Help & Support</h1>
            <p class="mt-3 text-sm leading-6 text-stone-600">Tell us what happened and we'll review it carefully.</p>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <x-ui.card class="mt-8">
            <form method="POST" action="{{ route('dashboard.support.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="space-y-5">
                    <x-ui.select name="reason" label="Reason" required>
                        <option value="">Choose a reason</option>
                        @foreach ($reasons as $value => $label)
                            <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="first_name" label="First name" :value="old('first_name', $user->first_name)" required />
                        <x-ui.input name="surname" label="Surname" :value="old('surname', $user->last_name)" required />
                    </div>

                    <x-ui.input name="email" label="Email" type="email" :value="old('email', $user->email)" required />

                    <x-ui.textarea name="comments" label="Comments" rows="6" required>{{ old('comments') }}</x-ui.textarea>

                    <x-ui.field name="image" label="Image" description="PNG, JPG, GIF, or WebP up to 4MB. Optional.">
                        <input id="image" name="image" type="file" accept="image/*" class="ui-input file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-900">
                    </x-ui.field>
                </div>

                <x-ui.button type="submit" variant="primary" size="lg" full-width class="mt-7 sm:w-auto">
                    Send support request
                </x-ui.button>
            </form>
        </x-ui.card>
    </main>
</x-dashboard.layout>
