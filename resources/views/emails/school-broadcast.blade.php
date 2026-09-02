<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $campaign->subject }}</title>
</head>
<body style="margin:0;background:#f1f5f9;color:#0f172a;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
    @php
        $schoolName = $campaign->school?->name ?: config('app.name');
        $initials = collect(preg_split('/\s+/', $schoolName))->filter()->take(2)->map(fn ($word) => strtoupper(substr($word, 0, 1)))->implode('');
    @endphp
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ \Illuminate\Support\Str::limit(strip_tags($campaign->body), 120) }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;overflow:hidden;border-radius:18px;background:#ffffff;box-shadow:0 14px 40px rgba(15,23,42,.10);">
                    <tr>
                        <td style="background:#172554;padding:24px 30px;color:#ffffff;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="width:48px;height:48px;border-radius:13px;background:#ffffff;color:#172554;text-align:center;font-size:18px;font-weight:800;">{{ $initials ?: 'SC' }}</td>
                                    <td style="padding-left:14px;">
                                        <div style="font-size:18px;font-weight:800;">{{ $schoolName }}</div>
                                        @if ($campaign->school?->motto)<div style="font-size:12px;color:#bfdbfe;">{{ $campaign->school->motto }}</div>@endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 30px;">
                            <p style="margin:0 0 18px;font-size:16px;">Hello {{ $recipient->recipient_name }},</p>
                            <div style="font-size:15px;color:#334155;">{!! $campaign->body !!}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #e2e8f0;background:#f8fafc;padding:20px 30px;font-size:12px;color:#64748b;">
                            This message was sent to {{ $recipient->email }} by {{ $schoolName }}.
                            @if ($campaign->school?->email)<br>Questions? Reply to {{ $campaign->school->email }}.@endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
