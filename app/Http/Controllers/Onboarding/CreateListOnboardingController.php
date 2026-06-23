<?php

namespace App\Http\Controllers\Onboarding;

use App\Domains\Auth\Actions\RegisterUserAction;
use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Lists\Actions\CreateDuaListAction;
use App\Domains\Onboarding\Services\OnboardingState;
use App\Domains\Onboarding\Services\OnboardingVerificationService;
use App\Http\Controllers\Controller;
use App\Models\DuaList;
use App\Models\User;
use App\Support\CreatorMode;
use App\Support\DuaListOccasions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CreateListOnboardingController extends Controller
{
    public function start(
        OnboardingState $state,
        UserEntitlementService $entitlements,
        OnboardingVerificationService $verification,
    ): RedirectResponse {
        $state->reset();

        return $this->redirectToFirstStep($state, $entitlements, $verification, creatorMode: false);
    }

    public function startCreator(
        Request $request,
        OnboardingState $state,
        UserEntitlementService $entitlements,
        OnboardingVerificationService $verification,
    ): RedirectResponse {
        if (! CreatorMode::enabled()) {
            abort(404);
        }

        $state->reset();
        $state->merge(['creator_mode' => true]);
        $this->prefillCreatorStateFromQuery($request, $state);

        return $this->redirectToFirstStep($state, $entitlements, $verification, creatorMode: true);
    }

    public function show(string $step, OnboardingState $state): View|RedirectResponse
    {
        if (! in_array($step, OnboardingState::STEPS, true)) {
            return redirect()->route('onboarding.start');
        }

        if (! in_array($step, $state->steps(), true)) {
            return redirect()->route('onboarding.show', $state->currentStep());
        }

        if ($redirect = $this->guardStep($step, $state)) {
            return $redirect;
        }

        $duaList = $this->completedList($state);

        return view("onboarding.{$step}", [
            'step' => $step,
            'stepIndex' => $state->stepIndex($step) + 1,
            'totalSteps' => $state->displayStepCount(),
            'previousStep' => $state->previousStep($step),
            'state' => $state->all(),
            'creatorMode' => $state->isCreatorMode(),
            'creatorModeEnabled' => CreatorMode::enabled(),
            'duaList' => $duaList,
            'shareUrl' => $duaList ? $duaList->publicUrl() : null,
            'coverImageUrl' => $state->get('image.cover_image_path')
                ? Storage::disk('public')->url($state->get('image.cover_image_path'))
                : null,
        ]);
    }

    public function store(
        Request $request,
        string $step,
        OnboardingState $state,
        RegisterUserAction $registerUser,
        CreateDuaListAction $createDuaList,
        OnboardingVerificationService $verification,
    ): RedirectResponse {
        if (! in_array($step, OnboardingState::STEPS, true)) {
            return redirect()->route('onboarding.start');
        }

        if (! in_array($step, $state->steps(), true)) {
            return redirect()->route('onboarding.show', $state->currentStep());
        }

        if ($redirect = $this->guardStep($step, $state)) {
            return $redirect;
        }

        return match ($step) {
            'account' => $this->storeAccount($request, $state, $registerUser, $verification),
            'list' => $this->storeList($request, $state),
            'dates' => $this->storeDates($request, $state),
            'image' => $this->storeImage($request, $state, $createDuaList),
            'fundraising' => $this->storeFundraising($request, $state, $createDuaList),
            'verify' => $this->storeVerification($request, $state, $createDuaList, $verification),
            default => redirect()->route('onboarding.show', $state->currentStep()),
        };
    }

    public function resend(OnboardingState $state, OnboardingVerificationService $verification): RedirectResponse
    {
        $user = User::query()->findOrFail($state->get('user_id'));
        $verification->resend($user);

        return redirect()
            ->route('onboarding.show', 'verify')
            ->with('resend_status', 'A new verification code has been sent to your email.');
    }

    private function redirectToFirstStep(
        OnboardingState $state,
        UserEntitlementService $entitlements,
        OnboardingVerificationService $verification,
        bool $creatorMode,
    ): RedirectResponse {
        if (Auth::check()) {
            if (! $entitlements->canCreateList(Auth::user())) {
                return redirect()
                    ->route('dashboard.upgrade', [
                        'product' => 'additional_list',
                    ])
                    ->withErrors(['billing' => 'You have reached the free list limit. Upgrade to create another list.']);
            }

            $data = [
                'user_id' => Auth::id(),
                'current_step' => 'list',
                'requires_verification' => ! Auth::user()->hasVerifiedEmail(),
            ];

            if ($data['requires_verification']) {
                $code = $verification->sendIfNeeded(Auth::user());
                if ($code !== null) {
                    $data['verification_code'] = $code;
                }
            }

            $state->merge($data);

            return redirect()->route('onboarding.show', 'list');
        }

        $state->merge(['current_step' => 'account']);

        return redirect()->route('onboarding.show', 'account');
    }

    private function prefillCreatorStateFromQuery(Request $request, OnboardingState $state): void
    {
        $fieldMap = [
            'FirstName' => 'account.first_name',
            'lastName' => 'account.last_name',
            'email' => 'account.email',
            'gender' => 'account.gender',
            'List_Name' => 'list.title',
            'Category_name' => 'list.occasion',
            'startDate' => 'dates.start_date',
            'endDate' => 'dates.end_date',
        ];

        $prefill = [];

        foreach ($fieldMap as $queryKey => $stateKey) {
            $value = $request->query($queryKey);

            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($queryKey === 'Category_name') {
                $value = strtolower($value);
            }

            if ($queryKey === 'gender') {
                $value = strtolower($value);
            }

            data_set($prefill, $stateKey, $value);
        }

        if ($prefill !== []) {
            $state->merge($prefill);
        }
    }

    private function storeAccount(
        Request $request,
        OnboardingState $state,
        RegisterUserAction $registerUser,
        OnboardingVerificationService $verification,
    ): RedirectResponse {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $authToken = $registerUser([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            'email' => $data['email'],
            'password' => $data['password'],
            'device_name' => 'onboarding',
        ]);

        /** @var User $user */
        $user = $authToken->user;
        Auth::login($user);
        $request->session()->regenerate();

        $code = $verification->sendIfNeeded($user);

        $state->merge([
            'user_id' => $user->id,
            'current_step' => 'list',
        ]);

        if ($code !== null) {
            $state->merge(['verification_code' => $code]);
        }

        return redirect()->route('onboarding.show', 'list');
    }

    private function storeList(Request $request, OnboardingState $state): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'occasion' => ['required', 'string', Rule::in(DuaListOccasions::keys())],
        ]);

        $state->merge([
            'list' => $data,
            'current_step' => 'dates',
        ]);

        return redirect()->route('onboarding.show', 'dates');
    }

    private function storeDates(Request $request, OnboardingState $state): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $state->merge([
            'dates' => $data,
            'current_step' => 'image',
        ]);

        return redirect()->route('onboarding.show', 'image');
    }

    private function storeImage(Request $request, OnboardingState $state, CreateDuaListAction $createDuaList): RedirectResponse
    {
        $data = $request->validate([
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $path = $state->get('image.cover_image_path');

        if ($request->boolean('remove_image')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            $path = $request->file('cover_image')->store('list-covers', 'public');
        }

        $state->merge([
            'image' => ['cover_image_path' => $path],
        ]);

        if ($state->isCreatorMode()) {
            $state->merge(['current_step' => 'fundraising']);

            return redirect()->route('onboarding.show', 'fundraising');
        }

        return $this->finalizeOnboardingAfterImage($state, $createDuaList);
    }

    private function storeFundraising(
        Request $request,
        OnboardingState $state,
        CreateDuaListAction $createDuaList,
    ): RedirectResponse {
        $data = $request->validate([
            'donation_link' => CreatorMode::donationLinkRules(),
            'donation_note' => ['required', 'string', 'max:500'],
        ]);

        $state->merge([
            'fundraising' => $data,
        ]);

        if ($state->get('requires_verification', true)) {
            $state->merge(['current_step' => 'verify']);

            return redirect()->route('onboarding.show', 'verify');
        }

        $duaList = $this->createListFromState($state, $createDuaList);

        $state->merge([
            'dua_list_id' => $duaList->id,
            'current_step' => 'success',
        ]);

        return redirect()->route('onboarding.show', 'success');
    }

    private function finalizeOnboardingAfterImage(OnboardingState $state, CreateDuaListAction $createDuaList): RedirectResponse
    {
        if ($state->get('requires_verification', true)) {
            $state->merge(['current_step' => 'verify']);

            return redirect()->route('onboarding.show', 'verify');
        }

        $duaList = $this->createListFromState($state, $createDuaList);

        $state->merge([
            'dua_list_id' => $duaList->id,
            'current_step' => 'success',
        ]);

        return redirect()->route('onboarding.show', 'success');
    }

    private function storeVerification(
        Request $request,
        OnboardingState $state,
        CreateDuaListAction $createDuaList,
        OnboardingVerificationService $verification,
    ): RedirectResponse {
        $data = $request->validate([
            'code' => ['required', 'array', 'size:4'],
            'code.*' => ['required', 'digits:1'],
        ]);

        $code = implode('', $data['code']);

        $user = User::query()->findOrFail($state->get('user_id'));

        try {
            $verification->verify($user, $code);
        } catch (ValidationException) {
            return back()
                ->withErrors(['code' => 'The verification code is incorrect.'])
                ->withInput();
        }

        Auth::login($user);

        $duaList = $this->createListFromState($state, $createDuaList);

        $state->merge([
            'verified' => true,
            'dua_list_id' => $duaList->id,
            'current_step' => 'success',
        ]);

        return redirect()->route('onboarding.show', 'success');
    }

    private function guardStep(string $step, OnboardingState $state): ?RedirectResponse
    {
        if ($step === 'success') {
            return $state->get('dua_list_id') ? null : redirect()->route('onboarding.start');
        }

        if ($step === 'account') {
            return Auth::check() ? redirect()->route('onboarding.show', 'list') : null;
        }

        if (! Auth::check() || ! $state->get('user_id')) {
            return redirect()->route('onboarding.show', 'account');
        }

        $requirements = [
            'dates' => 'list',
            'image' => 'dates',
            'fundraising' => 'image',
            'verify' => $state->isCreatorMode() ? 'fundraising' : 'image',
        ];

        $required = $requirements[$step] ?? null;

        if ($required && ! $state->get($required)) {
            return redirect()->route('onboarding.show', $state->currentStep());
        }

        if ($step === 'verify' && ! $state->get('requires_verification', true)) {
            return redirect()->route('onboarding.show', 'success');
        }

        return null;
    }

    private function completedList(OnboardingState $state): ?DuaList
    {
        $id = $state->get('dua_list_id');

        if (! $id) {
            return null;
        }

        return DuaList::query()->find($id);
    }

    private function createListFromState(OnboardingState $state, CreateDuaListAction $createDuaList): DuaList
    {
        $user = User::query()->findOrFail($state->get('user_id'));

        $payload = [
            'title' => $state->get('list.title'),
            'occasion' => $state->get('list.occasion'),
            'start_date' => $state->get('dates.start_date'),
            'end_date' => $state->get('dates.end_date'),
            'cover_image_path' => $state->get('image.cover_image_path'),
        ];

        if ($state->isCreatorMode()) {
            $payload['list_mode'] = CreatorMode::MODE_CREATOR;
            $payload['donation_link'] = $state->get('fundraising.donation_link');
            $payload['donation_note'] = $state->get('fundraising.donation_note');
        }

        return $createDuaList($user, $payload);
    }
}
