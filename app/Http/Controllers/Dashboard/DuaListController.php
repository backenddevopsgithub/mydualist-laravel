<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Lists\Actions\ArchiveDuaListAction;
use App\Domains\Lists\Actions\DeleteDuaListAction;
use App\Domains\Lists\Actions\RestoreDuaListAction;
use App\Domains\Lists\Actions\UpdateDuaListAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\UpdateListRequest;
use App\Models\DuaList;
use App\Support\DuaListOccasions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DuaListController extends Controller
{
    public function edit(DuaList $duaList): View
    {
        Gate::authorize('update', $duaList);

        return view('dashboard.lists.edit', [
            'user' => Auth::user(),
            'duaList' => $duaList,
            'occasions' => DuaListOccasions::labels(),
        ]);
    }

    public function update(UpdateListRequest $request, DuaList $duaList, UpdateDuaListAction $action): RedirectResponse
    {
        Gate::authorize('update', $duaList);

        $action($duaList, $request->validated());

        return redirect()
            ->route($duaList->isArchived() ? 'dashboard.archived' : 'dashboard')
            ->with('status', 'List updated successfully.');
    }

    public function archive(DuaList $duaList, ArchiveDuaListAction $action): RedirectResponse
    {
        Gate::authorize('archive', $duaList);
        $action($duaList);

        return redirect()->route('dashboard.archived')->with('status', 'List archived successfully.');
    }

    public function restore(DuaList $duaList, RestoreDuaListAction $action): RedirectResponse
    {
        Gate::authorize('restore', $duaList);
        $action($duaList);

        return redirect()->route('dashboard')->with('status', 'List restored successfully.');
    }

    public function destroy(DuaList $duaList, DeleteDuaListAction $action): RedirectResponse
    {
        Gate::authorize('delete', $duaList);
        $action($duaList);

        return redirect()->route('dashboard')->with('status', 'List deleted successfully.');
    }
}
