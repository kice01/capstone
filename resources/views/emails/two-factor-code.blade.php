<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ExTrack verification code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #17211b;">
    <h2>ExTrack security code</h2>
    <p>Hello {{ $user->name }},</p>
    <p>Use this one-time code to finish signing in:</p>
    <p style="font-size: 30px; font-weight: bold; letter-spacing: 8px;">{{ $code }}</p>
    <p>This code expires in 5 minutes.</p>
</body>
</html>