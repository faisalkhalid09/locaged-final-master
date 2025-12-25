<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password - {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 5px; margin: 20px 0; }
        .button { display: inline-block; background: #3498db; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; text-align: center; }
        .expiry { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; font-size: 14px; }
        .footer { text-align: center; color: #7f8c8d; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ config('app.name') }}</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->full_name }},</h2>
        
        <p>An account has been created for you on {{ config('app.name') }}. To get started, you need to set your password.</p>
        
        <p>Click the button below to set your password:</p>
        
        <p style="text-align: center;">
            <a href="{{ $setupUrl }}" class="button">Set Your Password</a>
        </p>
        
        <div class="expiry">
            <p><strong>⏰ Important:</strong> This link will expire in 24 hours for security reasons. Please set your password as soon as possible.</p>
        </div>
        
        <p>If the button doesn't work, copy and paste this URL into your browser:</p>
        <p style="word-break: break-all; font-size: 12px; color: #7f8c8d;">{{ $setupUrl }}</p>
        
        <p>Once you've set your password, you'll be able to access all features of the system.</p>
        
        <p>If you didn't expect this email or have any questions, please contact your system administrator.</p>
        
        <p>Best regards,<br>
        The {{ config('app.name') }} Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
