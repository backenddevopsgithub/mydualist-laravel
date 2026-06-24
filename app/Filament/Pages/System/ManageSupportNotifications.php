<?php

namespace App\Filament\Pages\System;

use App\Domains\Support\Services\SupportNotificationSettings as SupportNotificationSettingsService;
use App\Models\User;
use App\Policies\AnalyticsPolicy;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSupportNotifications extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Support Notifications';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.support-notification-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(AnalyticsPolicy::class)->viewAny($user);
    }

    public function mount(SupportNotificationSettingsService $settings): void
    {
        $this->form->fill([
            'recipients' => collect($settings->recipients())
                ->map(fn (string $email): array => ['email' => $email])
                ->all(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('recipients')
                    ->label('Notification recipients')
                    ->helperText('Each address receives an email when a support request is submitted.')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->reorderable(false)
                    ->addActionLabel('Add recipient'),
            ])
            ->statePath('data');
    }

    public function save(SupportNotificationSettingsService $settings): void
    {
        $state = $this->form->getState();
        $recipients = collect($state['recipients'] ?? [])
            ->pluck('email')
            ->all();

        $normalized = $settings->normalizeRecipients($recipients);

        if ($normalized === []) {
            Notification::make()
                ->title('Add at least one valid email address.')
                ->danger()
                ->send();

            return;
        }

        $settings->saveRecipients($normalized);

        Notification::make()
            ->title('Support notification recipients saved.')
            ->success()
            ->send();
    }
}
