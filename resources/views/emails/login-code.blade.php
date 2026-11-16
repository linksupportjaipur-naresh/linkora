<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your {{ config('app.name') }} code</title>
</head>
<body style="margin:0;padding:0;background:#F8F6FF;font-family:Arial,Helvetica,sans-serif;color:#1B1233">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F6FF;padding:32px 16px">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:20px;padding:36px 32px;border:1px solid #E3DBFA">
            <tr><td align="center" style="font-size:22px;font-weight:bold;color:#1B1233;padding-bottom:24px">
                <span style="display:inline-block;width:30px;height:30px;line-height:30px;border-radius:9px;background:#7955E8;color:#fff;font-size:16px;vertical-align:middle">&#128279;</span>
                <span style="vertical-align:middle;margin-left:6px">{{ config('app.name') }}</span>
            </td></tr>
            <tr><td align="center" style="font-size:22px;font-weight:bold;padding-bottom:10px">
                {{ $heading }}
            </td></tr>
            <tr><td align="center" style="font-size:15px;line-height:22px;color:#6B6384;padding-bottom:24px">
                Enter this code on {{ config('app.name') }} to {{ $action }}.
            </td></tr>
            <tr><td align="center" style="padding-bottom:24px">
                <div style="display:inline-block;background:#EDE7FF;color:#5B3BC4;border-radius:14px;padding:16px 28px;font-size:34px;font-weight:bold;letter-spacing:10px;font-family:'Courier New',monospace">{{ $code }}</div>
            </td></tr>
            <tr><td align="center" style="font-size:13px;line-height:20px;color:#6B6384">
                This code expires in {{ $minutes }} minutes and can be used once.<br>
                If you didn’t ask for it, you can safely ignore this email.
            </td></tr>
        </table>
        <p style="font-size:12px;color:#8F86B0;margin:18px 0 0">&copy; {{ date('Y') }} {{ config('app.name') }} · {{ config('linkora.address') }}</p>
    </td></tr>
</table>
</body>
</html>
