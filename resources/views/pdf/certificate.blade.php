<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} — {{ $recipient }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #0F172A;
            background: #FFFFFF;
        }
        .sheet {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            padding: 28px;
        }
        .frame {
            position: absolute;
            top: 28px; left: 28px; right: 28px; bottom: 28px;
            border: 3px solid #4F46E5;
        }
        .frame-inner {
            position: absolute;
            top: 38px; left: 38px; right: 38px; bottom: 38px;
            border: 1px solid #C7D2FE;
        }
        .content {
            position: absolute;
            top: 64px; left: 80px; right: 80px;
            text-align: center;
        }
        .brand {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 6px;
            color: #4F46E5;
        }
        .title {
            margin-top: 26px;
            font-size: 40px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .kind {
            margin-top: 6px;
            font-size: 15px;
            color: #475467;
        }
        .presented {
            margin-top: 34px;
            font-size: 14px;
            color: #667085;
        }
        .recipient {
            margin: 10px auto 0;
            padding-bottom: 8px;
            width: 70%;
            font-size: 34px;
            font-weight: bold;
            border-bottom: 1px solid #D0D5DD;
        }
        .lead {
            margin-top: 18px;
            font-size: 15px;
            color: #475467;
            line-height: 1.5;
        }
        .subject {
            margin-top: 6px;
            font-size: 22px;
            font-weight: bold;
            color: #0F172A;
        }
        .details {
            margin-top: 14px;
            font-size: 13px;
            color: #475467;
        }
        .details span { margin: 0 10px; }
        .footer {
            position: absolute;
            left: 80px; right: 80px; bottom: 64px;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { vertical-align: bottom; font-size: 12px; color: #475467; }
        .label { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #667085; }
        .value { margin-top: 3px; font-size: 13px; font-weight: bold; color: #0F172A; }
        .signature {
            width: 200px;
            border-top: 1px solid #98A2B3;
            padding-top: 6px;
        }
        .qr { text-align: right; }
        .qr img { width: 96px; height: 96px; }
        .verify { margin-top: 4px; font-size: 9px; color: #667085; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="frame"></div>
    <div class="frame-inner"></div>

    <div class="content">
        <div class="brand">EDULIFE</div>
        <div class="title">{{ $title }}</div>
        @if (!empty($kind))
            <div class="kind">{{ $kind }}</div>
        @endif

        <div class="presented">Ushbu sertifikat beriladi</div>
        <div class="recipient">{{ $recipient }}</div>

        <div class="lead">{{ $lead }}</div>
        <div class="subject">«{{ $subject }}»</div>

        @if (!empty($details))
            <div class="details">
                @foreach ($details as $detail)
                    <span>{{ $detail }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="footer">
        <table>
            <tr>
                <td style="width: 34%;">
                    <div class="label">Sertifikat raqami</div>
                    <div class="value">{{ $number }}</div>
                    <div class="label" style="margin-top: 10px;">Berilgan sana</div>
                    <div class="value">{{ $issuedAt }}</div>
                </td>
                <td style="width: 34%;">
                    @if (!empty($signatory))
                        <div class="signature">
                            <div class="value">{{ $signatory }}</div>
                            <div class="label">{{ $signatoryRole }}</div>
                        </div>
                    @endif
                </td>
                <td class="qr" style="width: 32%;">
                    @if (!empty($qrDataUri))
                        <img src="{{ $qrDataUri }}" alt="QR">
                    @endif
                    <div class="label" style="margin-top: 4px;">Tekshirish kodi: {{ $verificationCode }}</div>
                    <div class="verify">{{ $verifyUrl }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
