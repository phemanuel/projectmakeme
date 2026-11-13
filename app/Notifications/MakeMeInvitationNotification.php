<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MakeMeInvitationNotification extends Notification
{
    public function __construct(
        public string $token
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $setupUrl = route('makeme.invitation.show', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Your Invitation to NYFEW Project MakeMe Live')
            ->view('emails.makeme.invitation', [
                'participant' => $notifiable,
                'setupUrl' => $setupUrl,
            ]);
    }
}
