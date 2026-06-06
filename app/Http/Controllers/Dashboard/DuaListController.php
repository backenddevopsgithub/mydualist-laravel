<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Lists\Actions\ArchiveDuaListAction;
use App\Domains\Lists\Actions\DeleteDuaListAction;
use App\Domains\Lists\Actions\RestoreDuaListAction;
use App\Domains\Lists\Actions\UpdateDuaListAction;
use App\Http\Controllers\Controller;
use App\Models\DuaList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DuaListController extends Controller
{
    public function edit(DuaList $duaList): View
    {
        $this->authorizeOwner($duaList);

        return view('dashboard.lists.edit', [
            'user' => Auth::user(),
            'duaList' => $duaList,
            'occasions' => $this->occasions(),
        ]);
    }

    public function update(Request $request, DuaList $duaList, UpdateDuaListAction $action): RedirectResponse
    {
        $this->authorizeOwner($duaList);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'occasion' => ['required', 'string', Rule::in(array_keys($this->occasions()))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $action($duaList, $data);

        return redirect()
            ->route($duaList->isArchived() ? 'dashboard.archived' : 'dashboard')
            ->with('status', 'List updated successfully.');
    }

    public function archive(DuaList $duaList, ArchiveDuaListAction $action): RedirectResponse
    {
        $this->authorizeOwner($duaList);
        $action($duaList);

        return redirect()->route('dashboard.archived')->with('status', 'List archived successfully.');
    }

    public function restore(DuaList $duaList, RestoreDuaListAction $action): RedirectResponse
    {
        $this->authorizeOwner($duaList);
        $action($duaList);

        return redirect()->route('dashboard')->with('status', 'List restored successfully.');
    }

    public function destroy(DuaList $duaList, DeleteDuaListAction $action): RedirectResponse
    {
        $this->authorizeOwner($duaList);
        $action($duaList);

        return redirect()->route('dashboard')->with('status', 'List deleted successfully.');
    }

    private function authorizeOwner(DuaList $duaList): void
    {
        abort_unless($duaList->user_id === Auth::id(), 403);
    }

    /**
     * @return array<string, string>
     */
    private function occasions(): array
    {
        return [
            'umrah' => 'Umrah',
            'hajj' => 'Hajj',
            'ramadan' => 'Ramadan',
            'safar-travel' => 'Safar / Travel',
            'wedding' => 'Wedding',
            'aqiqah' => 'Aqiqah',
            'tahajjud' => 'Tahajjud',
            'quran-khatam' => 'Quran Khatam',
            'other' => 'Other',
        ];
    }
}
