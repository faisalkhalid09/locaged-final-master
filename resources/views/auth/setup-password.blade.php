<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Password - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('assets/L LOGO.svg') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f5; }
        .setup-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .setup-card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); max-width: 480px; width: 100%; padding: 48px 40px; }
        .logo-section { text-align: center; margin-bottom: 32px; }
        .logo-section img { height: 60px; margin-bottom: 16px; }
        .setup-header { text-align: center; margin-bottom: 32px; }
        .setup-header h1 { font-size: 28px; font-weight: 700; color: #1a1a1a; margin-bottom: 8px; }
        .setup-header p { color: #666; font-size: 15px; }
        .welcome-msg { background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 8px; margin-bottom: 24px; }
        .welcome-msg p { margin: 0; color: #1e40af; font-size: 14px; }
        .welcome-msg strong { color: #1e3a8a; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid #e5e5e5; border-radius: 8px; font-size: 15px; transition: all 0.2s; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .form-control.is-invalid { border-color: #ef4444; }
        .invalid-feedback { color: #ef4444; font-size: 13px; margin-top: 6px; display: block; }
        .password-hint { color: #666; font-size: 13px; margin-top: 6px; }
        .password-hint ul { margin: 8px 0 0 20px; }
        .password-hint li { margin: 4px 0; }
        .btn-submit { width: 100%; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 14px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3); }
        .btn-submit:active { transform: translateY(0); }
        .alert { padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-danger { background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b; }
        @media (max-width: 576px) {
            .setup-card { padding: 32px 24px; }
            .setup-header h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-card">
            <div class="logo-section">
                <img src="{{ asset('assets/L LOGO.svg') }}" alt="{{ config('app.name') }}" onerror="this.style.display='none'">
            </div>

            <div class="setup-header">
                <h1>Set Your Password</h1>
                <p>Create a secure password for your account</p>
            </div>

            <div class="welcome-msg">
                <p><strong>Welcome, {{ $user->full_name }}!</strong><br>Please set up your password to access your account.</p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.setup.store', ['user' => $user->id]) . '?' . http_build_query(request()->query()) }}">
                @csrf

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        id="password" 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        name="password" 
                        required
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <div class="password-hint">
                        Your password must include:
                        <ul>
                            <li>At least 8 characters</li>
                            <li>Uppercase and lowercase letters</li>
                            <li>Numbers and symbols</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input 
                        id="password_confirmation" 
                        type="password" 
                        class="form-control" 
                        name="password_confirmation" 
                        required
                        placeholder="Re-enter your password"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    Set Password & Login →
                </button>
            </form>
        </div>
    </div>
</body>
</html>
