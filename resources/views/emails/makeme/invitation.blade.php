
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NYFEW MakeMe Live Invitation</title>
</head>

<body style="margin:0;padding:0;background:#F3F1EB;font-family:Arial,Helvetica,sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="background:#F3F1EB;padding:35px 12px;">
    <tr>
        <td align="center">

            <table role="presentation" width="100%" cellpadding="0"
                   cellspacing="0"
                   style="max-width:600px;background:#FFFFFF;border-radius:16px;overflow:hidden;">

                <!-- Header -->
                <tr>
                    <td align="center"
                        style="background:#0B0B0D;padding:35px 25px;">

                        <img
                            src="{{ asset('images/nyfew-logo-new.png') }}"
                            alt="NYFEW Project MakeMe Live"
                            width="150"
                            style="display:block;max-width:150px;height:auto;margin:0 auto 18px;"
                        >

                        <div style="font-size:23px;font-weight:700;color:#F5DE94;">
                            PROJECT MAKEME LIVE
                        </div>

                        <div style="font-size:12px;color:#D6AD58;letter-spacing:2px;margin-top:8px;">
                            OFFICIAL PARTICIPANT INVITATION
                        </div>

                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:40px 32px;">

                        <p style="font-size:17px;color:#222;margin:0 0 20px;">
                            Hello {{ $participant->first_name }},
                        </p>

                        <h2 style="font-size:25px;line-height:1.3;color:#111;margin:0 0 20px;">
                            Welcome to Project MakeMe Live!
                        </h2>

                        <p style="font-size:15px;line-height:1.8;color:#444;">
                            Your participant profile has been created on the
                            <strong>NYFEW Project MakeMe Live Portal.</strong>
                        </p>

                        <p style="font-size:15px;line-height:1.8;color:#444;">
                            To access your dashboard and prepare for the show,
                            please create your personal password using the button below.
                        </p>

                        <!-- Participant ID -->
                        <table role="presentation" width="100%"
                               style="background:#FAF8F2;border:1px solid #EEE5CF;border-radius:10px;margin:25px 0;">
                            <tr>
                                <td style="padding:18px;text-align:center;">
                                    <div style="font-size:12px;color:#777;margin-bottom:6px;">
                                        YOUR PARTICIPANT NUMBER
                                    </div>

                                    <div style="font-size:22px;font-weight:bold;color:#B88735;">
                                        {{ $participant->participant_number }}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <!-- Button -->
                        <table role="presentation" cellpadding="0" cellspacing="0"
                               style="margin:30px auto;">
                            <tr>
                                <td bgcolor="#D6AD58" style="border-radius:8px;text-align:center;">
                                    <a href="{{ $setupUrl }}"
                                       style="display:inline-block;padding:17px 35px;color:#111;text-decoration:none;font-size:15px;font-weight:bold;">
                                        Create My Password
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:13px;color:#777;text-align:center;">
                            For security, this link expires in 60 minutes.
                        </p>

                        <p style="font-size:13px;line-height:1.7;color:#666;margin-top:28px;">
                            If the button doesn't work, copy this link into your browser:
                        </p>

                        <p style="font-size:12px;line-height:1.7;overflow-wrap:anywhere;">
                            <a href="{{ $setupUrl }}" style="color:#9B7025;">
                                {{ $setupUrl }}
                            </a>
                        </p>

                        <p style="font-size:14px;line-height:1.8;color:#444;margin-top:28px;">
                            If you weren't expecting this invitation, you can safely ignore this email.
                        </p>

                        <p style="font-size:14px;color:#333;margin-top:30px;">
                            Warm regards,<br>
                            <strong>NYFEW Project MakeMe Live Team</strong>
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:24px;background:#0B0B0D;text-align:center;">

                        <p style="font-size:13px;font-weight:bold;color:#F5DE94;margin:0 0 8px;">
                            NYFEW PROJECT MAKEME LIVE
                        </p>

                        <p style="font-size:12px;color:#BDBDBD;line-height:1.7;margin:0;">
                            This email was sent because your participant profile
                            was created for Project MakeMe Live.
                        </p>

                        <p style="font-size:12px;color:#BDBDBD;margin-top:15px;">
                            © {{ date('Y') }} NYFEW. All rights reserved.
                        </p>

                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
