<x-dashboard.layout :user="$user" title="Profile - My Dua List">
    <main class="mx-auto max-w-5xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10" x-data="{ tab: @js(request('tab', 'list-settings')) }">
        <h1 class="dashboard-page-title">Settings</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Manage your lists, account details, plan, and downloads.</p>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <x-ui.tabs class="mt-8">
            <x-ui.tab name="list-settings">List settings</x-ui.tab>
            <x-ui.tab name="profile-settings">Profile settings</x-ui.tab>
        </x-ui.tabs>

        <section x-show="tab === 'list-settings'" class="mt-8 space-y-6">
            <x-ui.card>
                <form method="POST" action="{{ route('dashboard.profile.list-settings') }}">
                    @csrf
                    @method('PATCH')

                    <h2 class="text-xl font-extrabold">List controls</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Choose a list and configure submission limits, display order, and email frequency.</p>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <x-ui.select name="dua_list_id" label="List" class="sm:col-span-2" required>
                            @foreach ($duaLists as $list)
                                <option value="{{ $list->id }}">{{ $list->title }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select name="dua_limit_per_person" label="Dua limits">
                            <option value="">Default batch limit</option>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }} per person</option>
                            @endfor
                        </x-ui.select>

                        <x-ui.select name="display_order" label="Display order" required>
                            <option value="date">Order by date</option>
                            <option value="gender">Order by gender</option>
                            <option value="person">Order by person</option>
                        </x-ui.select>

                        <x-ui.select name="email_frequency" label="Email frequency" class="sm:col-span-2" required>
                            <option value="every_submission">Every dua submission</option>
                            <option value="daily_summary">Daily summary</option>
                        </x-ui.select>
                    </div>

                    <x-ui.button type="submit" variant="primary" class="mt-7 sm:w-auto">Save list settings</x-ui.button>
                </form>
            </x-ui.card>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.card>
                    <form method="GET" action="{{ route('dashboard.profile.submissions.download') }}">
                        <h2 class="text-xl font-extrabold">Download submissions</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-600">Export all submissions for one list as CSV.</p>
                        <x-ui.select name="dua_list_id" label="List" class="mt-5" required>
                            @foreach ($duaLists as $list)
                                <option value="{{ $list->id }}">{{ $list->title }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.button type="submit" variant="primary" full-width class="mt-5">Download CSV</x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card>
                    <form method="POST" action="{{ route('dashboard.profile.list-image') }}" enctype="multipart/form-data">
                        @csrf
                        <h2 class="text-xl font-extrabold">List image</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-600">Upload or update the cover image for a list.</p>
                        <x-ui.select name="dua_list_id" label="List" class="mt-5" required>
                            @foreach ($duaLists as $list)
                                <option value="{{ $list->id }}">{{ $list->title }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.field name="cover_image" label="Cover image" class="mt-4">
                            <input name="cover_image" type="file" accept="image/*" class="ui-input file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-900" required>
                        </x-ui.field>
                        <x-ui.button type="submit" variant="primary" full-width class="mt-5">Upload image</x-ui.button>
                    </form>
                </x-ui.card>
            </div>
        </section>

        <section x-show="tab === 'profile-settings'" class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <x-ui.card>
                <form method="POST" action="{{ route('dashboard.profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 ring-1 ring-emerald-900/10">
                        Current plan: {{ $currentPlan }}
                    </div>

                    <h2 class="text-xl font-extrabold">Account details</h2>
                    <div class="mt-6 space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input name="first_name" label="First name" :value="old('first_name', $user->first_name)" required />
                            <x-ui.input name="last_name" label="Last name" :value="old('last_name', $user->last_name)" required />
                        </div>
                        <x-ui.input name="email" label="Email" type="email" :value="old('email', $user->email)" required />
                    </div>

                    <x-ui.button type="submit" variant="primary" class="mt-7 sm:w-auto">Save profile</x-ui.button>
                </form>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card>
                    <form method="POST" action="{{ route('dashboard.profile.password') }}">
                        @csrf
                        @method('PATCH')

                        <h2 class="text-xl font-extrabold">Change password</h2>
                        <div class="mt-6 space-y-5">
                            <x-ui.input name="current_password" label="Current password" type="password" required />
                            <x-ui.input name="password" label="New password" type="password" required />
                            <x-ui.input name="password_confirmation" label="Confirm password" type="password" required />
                        </div>

                        <x-ui.button type="submit" variant="primary" full-width class="mt-7">Update password</x-ui.button>
                    </form>
                </x-ui.card>

                <x-ui.card class="border-red-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <h2 class="text-xl font-extrabold">Logout</h2>
                        <p class="mt-2 text-sm leading-6 text-stone-600">Securely end this browser session.</p>
                        <x-ui.button type="submit" variant="secondary" class="mt-5 !border-red-200 !text-red-700 hover:!bg-red-50">Logout</x-ui.button>
                    </form>
                </x-ui.card>
            </div>
        </section>
    </main>
</x-dashboard.layout>
