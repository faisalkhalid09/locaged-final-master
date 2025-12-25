<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 5px; margin: 20px 0; }
        .credentials { background: #fff; padding: 15px; border-left: 4px solid #3498db; margin: 20px 0; }
        .credentials code { font-size: 14px; color: #2c3e50; }
        .footer { text-align: center;  color: #7f8c8d; font-size: 12px; margin-top: 30px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ config('app.name') }}</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->full_name }},</h2>
        
        <p>Your account has been created successfully. You can now access the system using the credentials below:</p>
        
        <div class="credentials">
            <p><strong>Email:</strong> <code>{{ $user->email }}</code></p>
            <p><strong>Password:</strong> <code>{{ $password }}</code></p>
        </div>
        
        <div class="warning">
            <p><strong>⚠️ Security Notice:</strong> Please change your password after your first login for security purposes.</p>
        </div>
        
        <p>To access your account, visit:<br>
        <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
        
        <p>If you have any questions or need assistance, please contact your system administrator.</p>
        
        <p>Best regards,<br>
        The {{ config('app.name') }} Team</p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
