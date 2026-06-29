<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation — BMMS</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 12px 30px rgba(23,32,42,0.08); padding: 40px; width: 100%; max-width: 440px; }
        .logo { font-size: 22px; font-weight: bold; color: #17202a; margin-bottom: 24px; }
        h1 { font-size: 20px; color: #17202a; margin-bottom: 6px; }
        .subtitle { color: #647184; font-size: 14px; margin-bottom: 28px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #17202a; margin-bottom: 5px; }
        input[type=password] {
            width: 100%; padding: 10px 12px; border: 1px solid #d8e0e8; border-radius: 6px;
            font-size: 14px; color: #17202a; margin-bottom: 18px; outline: none;
        }
        input[type=password]:focus { border-color: #1f6feb; }
        .error-list { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 14px; margin-bottom: 18px; color: #b42318; font-size: 13px; list-style: none; }
        .error-list li + li { margin-top: 4px; }
        .btn { width: 100%; padding: 12px; background: #1f6feb; color: #fff; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #1a5fcc; }
        .user-info { font-size: 13px; color: #647184; margin-bottom: 24px; padding: 10px 14px; background: #eef3f7; border-radius: 6px; }
        .hint { font-size: 12px; color: #9aacb8; margin-top: 6px; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">BMMS</div>
    <h1>Set Your Password</h1>
    <p class="subtitle">Complete your account setup to access the Board Meeting Management System.</p>

    <div class="user-info">
        Joining as: <strong>{{ $user->name }}</strong> ({{ $user->email }})
    </div>

    @if ($errors->any())
        <ul class="error-list">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('invite.accept', $token) }}">
        @csrf
        <label for="password">New Password</label>
        <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="At least 8 characters">

        <label for="password_confirmation">Confirm Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password">

        <button type="submit" class="btn">Activate Account</button>
        <p class="hint">You'll be logged in automatically after activating.</p>
    </form>
</div>
</body>
</html>
