<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function create(): View
    {
        return view('dashboard.support', [
            'user' => Auth::user(),
            'reasons' => $this->reasons(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys($this->reasons()))],
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:60'],
            'surname' => ['required', 'string', 'max:60'],
            'comments' => ['required', 'string', 'min:5', 'max:3000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('support-uploads', 'public')
            : null;

        SupportTicket::query()->create([
            'user_id' => Auth::id(),
            'reason' => $data['reason'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'surname' => $data['surname'],
            'comments' => $data['comments'],
            'image_path' => $imagePath,
        ]);

        return redirect()
            ->route('dashboard.support')
            ->with('status', 'Thanks. Your support request has been sent.');
    }

    /**
     * @return array<string, string>
     */
    private function reasons(): array
    {
        return [
            'general' => 'General Feedback and Connect',
            'bug' => 'Reporting a Bug',
            'upgrade' => 'Upgrade Not Working',
            'page' => 'Page Not Loading',
            'account' => 'Account Settings',
            'other' => 'Other',
        ];
    }
}
