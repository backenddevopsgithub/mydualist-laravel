@if ($showCommunityDuas ?? false)
    <section class="mt-6 space-y-4">
        @if (($statusCounts[App\Enums\DuaSubmissionStatus::Pending->value] ?? 0) === 0)
            <div class="rounded-[2rem] border border-emerald-100 bg-emerald-50/70 p-6 text-center">
                <h2 class="text-2xl font-extrabold text-emerald-950">Masha Allah. You're all caught up!</h2>
                <p class="mt-2 text-sm text-stone-600">Keep sharing your link to receive more dua requests.</p>
            </div>
        @endif

        @if ($communityDua)
            @include('dashboard.lists.partials.community-dua-card', [
                'communityDua' => $communityDua,
                'duaList' => $duaList,
            ])
        @else
            <div class="rounded-[2rem] border border-dashed border-stone-200 bg-white p-8 text-center">
                <p class="text-sm text-stone-600">No community duas are available right now. Check back later.</p>
            </div>
        @endif
    </section>
@endif
