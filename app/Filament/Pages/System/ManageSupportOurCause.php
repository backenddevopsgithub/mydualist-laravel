<?php

namespace App\Filament\Pages\System;

use App\Domains\Blog\Services\SupportOurCauseSettings;
use App\Models\User;
use App\Policies\AnalyticsPolicy;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageSupportOurCause extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Support Our Cause';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.manage-support-our-cause';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && app(AnalyticsPolicy::class)->viewAny($user);
    }

    public function mount(SupportOurCauseSettings $settings): void
    {
        $this->form->fill($settings->get());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('enabled')
                    ->label('Show on article pages')
                    ->default(true),
                TextInput::make('heading')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('primary_button_text')
                    ->label('Primary button text')
                    ->required()
                    ->maxLength(120),
                TextInput::make('primary_button_url')
                    ->label('Primary button URL')
                    ->url()
                    ->required()
                    ->maxLength(255),
                TextInput::make('secondary_button_text')
                    ->label('Secondary button text')
                    ->maxLength(120),
                TextInput::make('secondary_button_url')
                    ->label('Secondary button URL')
                    ->url()
                    ->maxLength(255),
                FileUpload::make('image_path')
                    ->label('Optional image/icon')
                    ->image()
                    ->directory('support-our-cause')
                    ->visibility('public'),
            ])
            ->statePath('data');
    }

    public function save(SupportOurCauseSettings $settings): void
    {
        $settings->save($this->form->getState());

        Notification::make()
            ->title('Support Our Cause settings saved.')
            ->success()
            ->send();
    }
}
