<x-dashboard.layout :user="$user" title="Profile - My Dua List">
    <main class="mx-auto max-w-4xl px-5 py-8 sm:px-6 lg:px-8 lg:py-10">
        <h1 class="font-serif text-4xl font-bold tracking-tight text-emerald-950">Profile Settings</h1>
        <p class="mt-3 text-sm leading-6 text-stone-600">Manage your account details and keep your login secure.</p>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 ring-1 ring-emerald-900/10">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <form method="POST" action="{{ route('dashboard.profile.update') }}" class="rounded-[2rem] border border-emerald-950/10 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.07)] sm:p-8">
                @csrf
                @method('PATCH')

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
        </div>
    </main>
</x-dashboard.layout>
