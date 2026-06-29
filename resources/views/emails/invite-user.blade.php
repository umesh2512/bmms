<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BMMS Invitation</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; padding: 40px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .logo { font-size: 22px; font-weight: bold; color: #17202a; margin-bottom: 24px; }
        h1 { font-size: 20px; color: #17202a; margin-bottom: 8px; }
        p { color: #647184; font-size: 15px; line-height: 1.6; }
        .btn { display: inline-block; margin: 24px 0; padding: 12px 28px; background: #1f6feb; color: #fff; text-decoration: none; border-radius: 6px; font-size: 15px; font-weight: 600; }
        .footer { font-size: 12px; color: #9aacb8; margin-top: 32px; border-top: 1px solid #d8e0e8; padding-top: 16px; }
        .url-fallback { font-size: 12px; color: #9aacb8; word-break: break-all; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">BMMS</div>
    <h1>You've been invited to join BMMS</h1>
    <p>Hello {{ $user->name }},</p>
    <p>You have been invited to join the <strong>Board Meeting Management System</strong>. Click the button below to set your password and activate your account.</p>
    <p>This invitation link will expire in <strong>72 hours</strong>.</p>
    <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>
    <p class="url-fallback">If the button doesn't work, copy and paste this link into your browser:<br>{{ $acceptUrl }}</p>
    <div class="footer">
        If you did not expect this invitation, you can safely ignore this email.
    </div>
</div>
</body>
</html>
