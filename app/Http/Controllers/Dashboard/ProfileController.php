<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Http\Controllers\Controller;
use App\Models\DuaList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function edit(UserEntitlementService $entitlements): View
    {
        $user = Auth::user();

        return view('dashboard.profile', [
            'user' => $user,
            'currentPlan' => $entitlements->planName($user),
            'duaLists' => $user->duaLists()->latest()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->forceFill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
        ])->save();

        return redirect()->route('dashboard.profile')->with('status', 'Profile updated successfully.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->forceFill([
            'password' => Hash::make($data['password']),
            'wp_password_hash' => null,
        ])->save();

        return redirect()->route('dashboard.profile')->with('status', 'Password changed successfully.');
    }

    public function listSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dua_list_id' => ['required', Rule::exists('dua_lists', 'id')->where('user_id', Auth::id())],
            'dua_limit_per_person' => ['nullable', 'integer', 'between:1,5'],
            'display_order' => ['required', Rule::in(['date', 'gender', 'person'])],
            'email_frequency' => ['required', Rule::in(['every_submission', 'daily_summary'])],
        ]);

        DuaList::query()
            ->whereKey($data['dua_list_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail()
            ->forceFill([
                'dua_limit_per_person' => $data['dua_limit_per_person'] ?? null,
                'display_order' => $data['display_order'],
                'email_frequency' => $data['email_frequency'],
            ])
            ->save();

        return redirect()
            ->route('dashboard.profile', ['tab' => 'list-settings'])
            ->with('status', 'List settings updated successfully.');
    }

    public function listImage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dua_list_id' => ['required', Rule::exists('dua_lists', 'id')->where('user_id', Auth::id())],
            'cover_image' => ['required', 'image', 'max:2048'],
        ]);

        $duaList = DuaList::query()
            ->whereKey($data['dua_list_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($duaList->cover_image_path) {
            Storage::disk('public')->delete($duaList->cover_image_path);
        }

        $duaList->forceFill([
            'cover_image_path' => $request->file('cover_image')->store('list-covers', 'public'),
        ])->save();

        return redirect()
            ->route('dashboard.profile', ['tab' => 'list-settings'])
            ->with('status', 'List image updated successfully.');
    }

    public function downloadSubmissions(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'dua_list_id' => ['required', Rule::exists('dua_lists', 'id')->where('user_id', Auth::id())],
        ]);

        $duaList = DuaList::query()
            ->whereKey($data['dua_list_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return response()->streamDownload(function () use ($duaList): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Status', 'Dua', 'Note', 'Submitted At']);

            $duaList->submissions()
                ->oldest()
                ->chunk(200, function ($submissions) use ($handle): void {
                    foreach ($submissions as $submission) {
                        fputcsv($handle, [
                            $submission->displayName(),
                            $submission->email,
                            $submission->status->value,
                            $submission->content,
                            $submission->note,
                            optional($submission->created_at)->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, 'dua-submissions-'.$duaList->id.'.csv', [
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
