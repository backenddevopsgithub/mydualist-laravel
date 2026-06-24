@php
    $sidebarLists = $sidebarLists ?? collect();
    $currentListId = request()->route('duaList')?->id;
@endphp

<div class="mx-auto grid max-w-7xl gap-2 text-sm font-bold text-stone-700">
    <a href="{{ route('dashboard') }}" @class([
        'rounded-xl px-3 py-3',
        'bg-emerald-50 text-emerald-900' => request()->routeIs('dashboard', 'dashboard.archived') && ! request()->routeIs('dashboard.lists.*'),
        'hover:bg-emerald-50' => ! request()->routeIs('dashboard', 'dashboard.archived') || request()->routeIs('dashboard.lists.*'),
    ])>All Lists</a>

    @forelse ($sidebarLists as $list)
        <a
            href="{{ route('dashboard.lists.show', $list) }}"
            @class([
                'rounded-xl px-3 py-3 pl-6',
                'bg-emerald-50 text-emerald-900' => request()->routeIs('dashboard.lists.show') && $currentListId === $list->id,
                'hover:bg-emerald-50' => ! (request()->routeIs('dashboard.lists.show') && $currentListId === $list->id),
            ])
        >
            {{ $list->title }}
        </a>
    @empty
        <p class="px-3 py-2 text-xs font-medium text-stone-500">No lists found</p>
    @endforelse

    <a href="{{ route('dashboard', ['tab' => 'archived']) }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Archived Lists</a>
    <a href="{{ route('dashboard.profile') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Profile</a>
    <a href="{{ route('dashboard.upgrade') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Upgrade Plan</a>
    <a href="{{ route('dashboard.submissions') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">My Submissions</a>
    <a href="{{ route('dashboard.support') }}" class="rounded-xl px-3 py-3 hover:bg-emerald-50">Help & Support</a>

    <x-dashboard.logout-form class="rounded-xl px-3 py-3 hover:bg-red-50" />
</div>
