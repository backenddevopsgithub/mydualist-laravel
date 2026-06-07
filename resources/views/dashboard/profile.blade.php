<x-dashboard.layout :user="$user" title="Profile - My Dua List">
    <main class="mx-auto max-w-5xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10" x-data="{ tab: @js(request('tab', 'list-settings')) }">
        <h1 class="font-serif text-4xl font-bold tracking-tight text-emerald-950">Settings</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Manage your lists, account details, plan, and downloads.</p>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-8 flex gap-2 overflow-x-auto rounded-[1.5rem] border border-emerald-950/10 bg-white p-2 shadow-sm">
            <button type="button" x-on:click="tab = 'list-settings'" x-bind:class="tab === 'list-settings' ? 'bg-emerald-900 text-white' : 'text-stone-700 hover:bg-emerald-50'" class="shrink-0 rounded-2xl px-5 py-3 text-sm font-extrabold transition">List Settings</button>
            <button type="button" x-on:click="tab = 'profile-settings'" x-bind:class="tab === 'profile-settings' ? 'bg-emerald-900 text-white' : 'text-stone-700 hover:bg-emerald-50'" class="shrink-0 rounded-2xl px-5 py-3 text-sm font-extrabold transition">Profile Settings</button>
        </div>

        <section x-show="tab === 'list-settings'" class="mt-8 space-y-6">
            <form method="POST" action="{{ route('dashboard.profile.list-settings') }}" class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
                @csrf
                @method('PATCH')

                <h2 class="text-xl font-extrabold">List Controls</h2>
                <p class="mt-2 text-sm leading-6 text-stone-600">Choose a list and configure submission limits, display order, and email frequency.</p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="settings_dua_list_id" class="block text-sm font-bold text-stone-900">List</label>
                        <select id="settings_dua_list_id" name="dua_list_id" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                            @foreach ($duaLists as $list)
                                <option value="{{ $list->id }}">{{ $list->title }}</option>
                            @endforeach
                        </select>
                        @error('dua_list_id') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="dua_limit_per_person" class="block text-sm font-bold text-stone-900">Dua Limits</label>
                        <select id="dua_limit_per_person" name="dua_limit_per_person" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100">
                            <option value="">Default batch limit</option>
                            @for ($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }} per person</option>
                            @endfor
                        </select>
                        @error('dua_limit_per_person') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="display_order" class="block text-sm font-bold text-stone-900">Duas Display Order</label>
                        <select id="display_order" name="display_order" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                            <option value="date">Order by Date</option>
                            <option value="gender">Order by Gender</option>
                            <option value="person">Order by Person</option>
                        </select>
                        @error('display_order') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="email_frequency" class="block text-sm font-bold text-stone-900">Frequency of Emails</label>
                        <select id="email_frequency" name="email_frequency" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                            <option value="every_submission">Every Dua Submission</option>
                            <option value="daily_summary">Daily Summary</option>
                        </select>
                        @error('email_frequency') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" class="mt-7 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800 sm:w-auto">Save List Settings</button>
            </form>

            <div class="grid gap-6 lg:grid-cols-2">
                <form method="GET" action="{{ route('dashboard.profile.submissions.download') }}" class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
                    <h2 class="text-xl font-extrabold">Download Dua Submissions</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Export all submissions for one list as CSV.</p>
                    <select name="dua_list_id" class="mt-5 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        @foreach ($duaLists as $list)
                            <option value="{{ $list->id }}">{{ $list->title }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="mt-5 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">Download CSV</button>
                </form>

                <form method="POST" action="{{ route('dashboard.profile.list-image') }}" enctype="multipart/form-data" class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
                    @csrf
                    <h2 class="text-xl font-extrabold">List Image</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Upload or update the cover image for a list.</p>
                    <select name="dua_list_id" class="mt-5 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        @foreach ($duaLists as $list)
                            <option value="{{ $list->id }}">{{ $list->title }}</option>
                        @endforeach
                    </select>
                    <input name="cover_image" type="file" accept="image/*" class="mt-4 block w-full rounded-2xl border border-dashed border-emerald-900/20 bg-emerald-50/50 px-4 py-4 text-sm font-semibold text-stone-700" required>
                    @error('cover_image') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="mt-5 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">Upload Image</button>
                </form>
            </div>
        </section>

        <section x-show="tab === 'profile-settings'" class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <form method="POST" action="{{ route('dashboard.profile.update') }}" class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
                @csrf
                @method('PATCH')

                <div class="mb-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-extrabold text-emerald-900 ring-1 ring-emerald-900/10">
                    Current Plan: {{ $currentPlan }}
                </div>

                <h2 class="text-xl font-extrabold">Account Details</h2>
                <div class="mt-6 space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="block text-sm font-bold text-stone-900">First Name</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                            @error('first_name') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-bold text-stone-900">Last Name</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                            @error('last_name') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-bold text-stone-900">Email Address</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        @error('email') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" class="mt-7 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800 sm:w-auto">Save Profile</button>
            </form>

            <div class="space-y-6">
                <form method="POST" action="{{ route('dashboard.profile.password') }}" class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
                    @csrf
                    @method('PATCH')

                    <h2 class="text-xl font-extrabold">Change Password</h2>
                    <div class="mt-6 space-y-5">
                        <div>
                            <label for="current_password" class="block text-sm font-bold text-stone-900">Current Password</label>
                            <input id="current_password" name="current_password" type="password" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                            @error('current_password') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-bold text-stone-900">New Password</label>
                            <input id="password" name="password" type="password" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                            @error('password') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-bold text-stone-900">Confirm Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-700 focus:ring-4 focus:ring-emerald-100" required>
                        </div>
                    </div>

                    <button type="submit" class="mt-7 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white transition hover:bg-emerald-800">Update Password</button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="rounded-[2rem] border border-red-100 bg-white p-6 shadow-sm sm:p-8">
                    @csrf
                    <h2 class="text-xl font-extrabold">Logout</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-600">Securely end this browser session.</p>
                    <button type="submit" class="mt-5 rounded-2xl bg-red-50 px-5 py-3 text-sm font-extrabold text-red-700 transition hover:bg-red-100">Logout</button>
                </form>
            </div>
        </section>
    </main>
</x-dashboard.layout>
