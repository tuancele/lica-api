<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait Sendmail
{
    public function send($blade, $title, $email, $body)
    {
        try {
            // Validate email address
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Sendmail: Invalid email address', ['email' => $email]);

                return false;
            }

            // Get mail configuration
            $mailDriver = getConfig('mail_driver');
            $smtpHost = getConfig('smtp_host');
            $smtpPort = getConfig('smtp_port');

            // Check if mail is configured
            if (empty($mailDriver) || empty($smtpHost)) {
                Log::warning('Sendmail: Mail configuration is missing', [
                    'driver' => $mailDriver,
                    'host' => $smtpHost,
                ]);

                return false;
            }

            $config = [
                'driver' => $mailDriver,
                'host' => $smtpHost,
                'port' => $smtpPort ?: 587,
                'from' => [
                    'address' => getConfig('email_send') ?: config('mail.from.address'),
                    'name' => getConfig('email_name_send') ?: config('mail.from.name'),
                ],
                'encryption' => getConfig('smtp_encryption') ?: 'tls',
                'username' => getConfig('smtp_email'),
                'password' => getConfig('smtp_password'),
            ];

            Config::set('mail', $config);

            $data = [
                'name' => $title,
                'body' => $body,
                'email' => $email,
            ];

            Mail::send($blade, ['data' => $data], function ($message) use ($title, $data) {
                $fromEmail = getConfig('email_send') ?: config('mail.from.address');
                $fromName = getConfig('email_name_send') ?: config('mail.from.name');

                $message->to($data['email'])->subject($title);
                $message->from($fromEmail, $fromName);
            });

            Log::info('Sendmail: Email sent successfully', [
                'to' => $email,
                'subject' => $title,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Sendmail: Failed to send email', [
                'to' => $email,
                'subject' => $title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
