<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Support\CreatorMode;
use App\Domains\Lists\Services\DuaListQueryService;
use App\Domains\Profile\Actions\ChangeUserPasswordAction;
use App\Domains\Profile\Actions\ExportDuaSubmissionsAction;
use App\Domains\Profile\Actions\UpdateCreatorModeSettingsAction;
use App\Domains\Profile\Actions\UpdateListSettingsAction;
use App\Domains\Profile\Actions\UpdateUserProfileAction;
use App\Domains\Profile\Actions\UploadListImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DownloadSubmissionsRequest;
use App\Http\Requests\Profile\UpdateCreatorModeSettingsRequest;
use App\Http\Requests\Profile\UpdateListSettingsRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadListImageRequest;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function edit(UserEntitlementService $entitlements, DuaListQueryService $lists): View
    {
        $user = Auth::user();

        return view('dashboard.profile', [
            'user' => $user,
            'currentPlan' => $entitlements->planName($user),
            'duaLists' => $lists->listsForProfile($user),
            'creatorLists' => CreatorMode::enabled()
                ? $lists->creatorListsForProfile($user)
                : collect(),
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateUserProfileAction $action): RedirectResponse
    {
        ($action)($request->user(), $request->validated());

        return redirect()->route('dashboard.profile')->with('status', 'Profile updated successfully.');
    }

    public function password(UpdatePasswordRequest $request, ChangeUserPasswordAction $action): RedirectResponse
    {
        Impersonation::ensureSensitiveActionAllowed();

        ($action)($request->user(), $request->validated());

        return redirect()->route('dashboard.profile')->with('status', 'Password changed successfully.');
    }

    public function listSettings(UpdateListSettingsRequest $request, UpdateListSettingsAction $action): RedirectResponse
    {
        ($action)($request->user(), $request->validated());

        return redirect()
            ->route('dashboard.profile', ['tab' => 'list-settings'])
            ->with('status', 'List settings updated successfully.');
    }

    public function creatorModeSettings(
        UpdateCreatorModeSettingsRequest $request,
        UpdateCreatorModeSettingsAction $action,
    ): RedirectResponse {
        ($action)($request->user(), $request->validated());

        return redirect()
            ->route('dashboard.profile', ['tab' => 'list-settings'])
            ->with('status', 'Creator Mode settings updated successfully.');
    }

    public function listImage(UploadListImageRequest $request, UploadListImageAction $action): RedirectResponse
    {
        ($action)($request->user(), $request->validated(), $request->file('cover_image'));

        return redirect()
            ->route('dashboard.profile', ['tab' => 'list-settings'])
            ->with('status', 'List image updated successfully.');
    }

    public function downloadSubmissions(
        DownloadSubmissionsRequest $request,
        ExportDuaSubmissionsAction $action,
    ): StreamedResponse {
        $export = ($action)($request->user(), (int) $request->validated('dua_list_id'));

        return response()->streamDownload($export['callback'], $export['filename'], [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
