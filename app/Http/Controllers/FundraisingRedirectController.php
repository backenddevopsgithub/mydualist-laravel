<?php

namespace App\Http\Controllers;

use App\Models\DuaList;
use App\Support\CreatorMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FundraisingRedirectController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $url = (string) $request->query('redirecting', '');
        $listId = $request->query('list_id');
        $tracking = (string) $request->query('tracking', '');
        $bypass = (string) $request->query('bypass', '');

        if ($url === '' || $listId === null || $tracking === '' || $bypass === '') {
            abort(404);
        }

        if (! CreatorMode::enabled()) {
            return redirect()->away($url);
        }

        $duaList = DuaList::query()
            ->where(function ($query) use ($listId): void {
                $query->where('wp_post_id', $listId)
                    ->orWhere('id', $listId);
            })
            ->first();

        if ($duaList !== null && $duaList->showsCreatorFeatures()) {
            $duaList->increment('insights_clicks');
        }

        return redirect()->away($url);
    }

    public function trackView(Request $request, DuaList $duaList): Response
    {
        if (! CreatorMode::enabled() || ! $duaList->showsCreatorFeatures()) {
            return response()->noContent();
        }

        $duaList->increment('insights_views');

        return response()->noContent();
    }
}
