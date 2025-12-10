<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;
    
    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url(config('app.frontend_url') . '/reset-password/' . $this->token . '?email=' . urlencode($this->email));
        
        return (new MailMessage)
            ->subject('Jelszó visszaállítás')
            ->line('Kaptál egy jelszó visszaállítási kérelmet.')
            ->action('Jelszó visszaállítása', $url)
            ->line('Ez a link 60 percig érvényes.')
            ->line('Ha nem te kérted, hagyd figyelmen kívül!');
    }
}