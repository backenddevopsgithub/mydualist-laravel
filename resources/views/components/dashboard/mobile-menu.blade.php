<div class="mx-auto grid max-w-7xl gap-2 text-sm font-bold text-stone-700">
    <a href="{{ route('dashboard') }}" class="rounded-xl bg-emerald-50 px-3 py-3 text-emerald-900">Dashboard</a>
    <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">My Lists</a>
    <a href="{{ route('dashboard', ['tab' => 'archived']) }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Archived Lists</a>
    <a href="{{ route('dashboard.profile') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Profile</a>
    <a href="{{ route('dashboard.upgrade') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Upgrade Plan</a>
    <a href="{{ route('dashboard.submissions') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">My Submissions</a>
    <a href="{{ route('dashboard.support') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Help & Support</a>
</div>
