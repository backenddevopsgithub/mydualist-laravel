<?php

namespace App\Filament\Resources\ReportedDuaSubmissionResource\Pages;

use App\Domains\Submissions\Actions\DismissReportedDuaSubmissionAction;
use App\Domains\Submissions\Actions\HideReportedDuaSubmissionAction;
use App\Domains\Submissions\Actions\RestoreReportedDuaSubmissionAction;
use App\Enums\DuaSubmissionStatus;
use App\Filament\Resources\ReportedDuaSubmissionResource;
use App\Models\DuaSubmission;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReportedDuaSubmission extends ViewRecord
{
    protected static string $resource = ReportedDuaSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hide')
                ->label('Hide submission')
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status !== DuaSubmissionStatus::Hidden)
                ->authorize(fn (): bool => auth()->user()?->can('moderate', $this->record) ?? false)
                ->requiresConfirmation()
                ->form([
                    Textarea::make('moderation_notes')
                        ->label('Moderation notes')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    /** @var DuaSubmission $record */
                    $record = $this->record;
                    /** @var User $moderator */
                    $moderator = auth()->user();

                    app(HideReportedDuaSubmissionAction::class)(
                        $record,
                        $moderator,
                        $data['moderation_notes'] ?? null,
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title('Submission hidden')
                        ->success()
                        ->send();
                }),
            Action::make('restore')
                ->label('Restore submission')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(fn (): bool => $this->record->status !== DuaSubmissionStatus::Pending)
                ->authorize(fn (): bool => auth()->user()?->can('moderate', $this->record) ?? false)
                ->requiresConfirmation()
                ->form([
                    Textarea::make('moderation_notes')
                        ->label('Moderation notes')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    /** @var DuaSubmission $record */
                    $record = $this->record;
                    /** @var User $moderator */
                    $moderator = auth()->user();

                    app(RestoreReportedDuaSubmissionAction::class)(
                        $record,
                        $moderator,
                        $data['moderation_notes'] ?? null,
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title('Submission restored to pending')
                        ->success()
                        ->send();
                }),
            Action::make('dismiss')
                ->label('Dismiss report')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->authorize(fn (): bool => auth()->user()?->can('moderate', $this->record) ?? false)
                ->requiresConfirmation()
                ->form([
                    Textarea::make('moderation_notes')
                        ->label('Moderation notes')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    /** @var DuaSubmission $record */
                    $record = $this->record;
                    /** @var User $moderator */
                    $moderator = auth()->user();

                    app(DismissReportedDuaSubmissionAction::class)(
                        $record,
                        $moderator,
                        $data['moderation_notes'] ?? null,
                    );

                    Notification::make()
                        ->title('Report dismissed')
                        ->success()
                        ->send();

                    $this->redirect(ReportedDuaSubmissionResource::getUrl('index'));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record->load(['duaList', 'moderatedBy', 'moderationLogs.moderator']))
            ->schema([
                Section::make('Submission')
                    ->schema([
                        TextEntry::make('display_name')
                            ->label('Submitter')
                            ->state(fn (DuaSubmission $record): string => $record->displayName()),
                        TextEntry::make('email'),
                        TextEntry::make('duaList.title')->label('List title'),
                        TextEntry::make('duaList.occasion')
                            ->label('List occasion')
                            ->formatStateUsing(fn (?string $state): string => $state ? str($state)->headline()->toString() : '—'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('content')->columnSpanFull(),
                        TextEntry::make('note')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('created_at')->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Report details')
                    ->schema([
                        TextEntry::make('report_reason')->badge(),
                        TextEntry::make('report_note')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('report_count'),
                        TextEntry::make('reported_at')->dateTime(),
                        TextEntry::make('status_before_report')
                            ->label('Status before report')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Latest moderation')
                    ->schema([
                        TextEntry::make('moderatedBy.name')
                            ->label('Moderated by')
                            ->placeholder('—'),
                        TextEntry::make('moderated_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('moderation_action')
                            ->label('Moderation action')
                            ->placeholder('—')
                            ->formatStateUsing(fn (?string $state): string => $state ? str($state)->headline()->toString() : '—'),
                        TextEntry::make('moderation_notes')
                            ->label('Moderation notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Moderation history')
                    ->schema([
                        RepeatableEntry::make('moderationLogs')
                            ->label('')
                            ->schema([
                                TextEntry::make('created_at')->dateTime()->label('When'),
                                TextEntry::make('action')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state?->label() ?? '—'),
                                TextEntry::make('moderator.name')
                                    ->label('Moderator')
                                    ->placeholder('System'),
                                TextEntry::make('previous_status')
                                    ->label('From')
                                    ->placeholder('—'),
                                TextEntry::make('new_status')
                                    ->label('To')
                                    ->placeholder('—'),
                                TextEntry::make('notes')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->placeholder('No moderation actions recorded yet.'),
                    ]),
            ]);
    }
}
