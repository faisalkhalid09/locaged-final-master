<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * AUDIT: SMTP Verification Command
 * 
 * Tests email configuration by sending a test email.
 * Fails loudly if SMTP is misconfigured.
 * 
 * Usage: php artisan test:email admin@example.com
 */
class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:email 
                            {email : The email address to send test email to}
                            {--subject= : Custom subject line}';

    /**
     * The console command description.
     */
    protected $description = 'Send a test email to verify SMTP configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $subject = $this->option('subject') ?? 'LocaGed SMTP Test - ' . now()->format('Y-m-d H:i:s');

        $this->info('Testing SMTP configuration...');
        $this->newLine();

        // Display current mail configuration
        $this->table(
            ['Setting', 'Value'],
            [
                ['MAIL_MAILER', config('mail.default')],
                ['MAIL_HOST', config('mail.mailers.smtp.host')],
                ['MAIL_PORT', config('mail.mailers.smtp.port')],
                ['MAIL_USERNAME', config('mail.mailers.smtp.username') ? '***' : '(not set)'],
                ['MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption') ?? 'none'],
                ['MAIL_FROM_ADDRESS', config('mail.from.address')],
                ['MAIL_FROM_NAME', config('mail.from.name')],
            ]
        );

        $this->newLine();

        // Check for common misconfigurations
        if (config('mail.default') === 'log') {
            $this->error('❌ MAIL_MAILER is set to "log" - emails are only being logged, not sent!');
            $this->warn('   Set MAIL_MAILER=smtp in your .env file for production.');
            return Command::FAILURE;
        }

        if (config('mail.default') === 'array') {
            $this->error('❌ MAIL_MAILER is set to "array" - emails are not being sent!');
            return Command::FAILURE;
        }

        if (empty(config('mail.from.address')) || config('mail.from.address') === 'hello@example.com') {
            $this->warn('⚠️  MAIL_FROM_ADDRESS appears to be using default value.');
        }

        // Attempt to send the test email
        $this->info("Sending test email to: {$email}");

        try {
            Mail::raw(
                $this->getEmailBody(),
                function ($message) use ($email, $subject) {
                    $message->to($email)
                            ->subject($subject);
                }
            );

            $this->newLine();
            $this->info('✅ Test email sent successfully!');
            $this->info("   Check inbox for: {$email}");
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Failed to send test email!');
            $this->newLine();
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();

            // Provide troubleshooting tips
            $this->warn('Troubleshooting tips:');
            $this->line('  1. Verify SMTP credentials in .env file');
            $this->line('  2. Check if MAIL_HOST is reachable from server');
            $this->line('  3. Ensure correct MAIL_PORT (587 for TLS, 465 for SSL)');
            $this->line('  4. Check firewall rules for outbound SMTP');
            $this->line('  5. Some providers require app-specific passwords');
            $this->newLine();

            return Command::FAILURE;
        }
    }

    /**
     * Generate the email body content.
     */
    private function getEmailBody(): string
    {
        $appName = config('app.name', 'LocaGed');
        $appUrl = config('app.url', 'http://localhost');
        $timestamp = now()->format('Y-m-d H:i:s T');
        $serverName = $_SERVER['SERVER_NAME'] ?? gethostname();

        return <<<EOT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{$appName} - SMTP Configuration Test
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

This is a test email to verify your SMTP configuration.

If you received this email, your mail settings are working correctly!

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Configuration Details:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Sent at: {$timestamp}
Application: {$appName}
URL: {$appUrl}
Server: {$serverName}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

This email was sent by the test:email artisan command.
For support, contact your system administrator.
EOT;
    }
}
