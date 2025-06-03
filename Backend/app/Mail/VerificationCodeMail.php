<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationCode;

    public function __construct($verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    public function build()
    {
        $html = "
            <html>
                <body style='font-family: Arial, sans-serif; color: #333;'>
                    <h2>Verification Code</h2>
                    <p>Your verification code is: 
                        <strong style='font-size: 20px; color: #2d3748;'>{$this->verificationCode}</strong>
                    </p>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn't request this code, please ignore this email.</p>
                </body>
            </html>
        ";

        return $this->subject('Verification Code')
                    ->html($html); // ✅ إرسال كود HTML مباشرة
    }
}
