<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public ?string $code = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'E-posta Doğrulama');
    }

    public function content(): Content
    {
        if ($this->code) {
            return new Content(
                htmlString: "<p>Merhaba {$this->user->username},</p><p>E-posta doğrulama kodunuz: <strong>{$this->code}</strong></p><p>Kod 30 dakika geçerlidir.</p>",
            );
        }

        $url = URL::temporarySignedRoute(
            'api.email.verify',
            now()->addHours(24),
            ['id' => $this->user->id, 'hash' => sha1($this->user->email)]
        );

        return new Content(
            htmlString: "<p>Merhaba {$this->user->username},</p><p>E-postanızı doğrulamak için <a href=\"{$url}\">buraya tıklayın</a>.</p>",
        );
    }
}
