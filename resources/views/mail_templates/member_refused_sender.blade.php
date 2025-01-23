<!DOCTYPE html>
<html lang="en">

<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="background-color: #ffffff; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">

    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff;">
        <tbody>
            <tr>
                <td>
                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                                        style="background-color: #0018A8; color: #000000; width: 600px; margin: 0 auto;">
                                        <tbody>
                                            <tr>
                                                <td style="text-align: left; padding: 20px 50px;">
                                                    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td style="width:100%; text-align: center;">
                                                                <div style="max-width: 250px; text-align: center; margin: 0 auto;">
                                                                    <img src="https://signature1618.app/backend_code/public/signaturelogo.png"
                                                                        style="display: block; margin: 0 auto; height:60%; border: 0; width: 60%;">
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                                        style="background-color: #ffffff; border-left: 1px solid #ebebeb; border-right: 1px solid #ebebeb; color: #000000; width: 600px; margin: 0 auto;">
                                        <tbody>
                                            <tr>
                                                <td style="text-align: left; padding: 20px;">
                                                    <h1 style="margin: 0; color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 400; line-height: 120%;">
                                                        Dear {{ rtrim($user_d['first_name']) }} {{ rtrim($user_d['last_name']) }},
                                                    </h1>
                                                    <p style="color: #101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 400; line-height: 120%;">
                                                        {{$user_d['invited_member_name']}} has declined to join {{$user_d['organization_name']}}'s team on Signature1618.
                                                    </p>
                                                    <br/>
                                                    <p><strong>Refusing invitee:</strong></p>
                                                    <table style="border: 1px solid #101112; border-collapse: collapse; width:100%; font-family: Arial, Helvetica, sans-serif; font-size: 16px;">
                                                        <tr>
                                                            <td style="border: 1px solid #101112; padding: 8px;">Name</td>
                                                            <td style="border: 1px solid #101112; padding: 8px;">{{$user_d['invited_member_name']}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="border: 1px solid #101112; padding: 8px;">Job Title</td>
                                                            <td style="border: 1px solid #101112; padding: 8px;">{{$user_d['job_title']}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="border: 1px solid #101112; padding: 8px;">Invitation Sent Date</td>
                                                            <td style="border: 1px solid #101112; padding: 8px;">{{ \App\Helpers\Common::onlyDateFormat($user_d['invitation_date']) }}</td>
                                                        </tr>
                                                    </table>
                                                    <p style="color: #101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 400; line-height: 120%; margin-bottom: 16px;">
                                                        If you have need assistance feel free to reach out to our support team.
                                                    </p>
                                                    <br>
                                                    <p style="color: #101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 400; line-height: 120%;">
                                                        Best regards,<br>Signature1618 Support
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                                        style="background-color: #0018A8; padding:20px; color: #000000; width: 600px; margin: 0 auto;">
                                        <tbody>
                                            <tr>
                                                <td style="text-align: center; padding: 5px;">
                                                    <p style="color: #ebebeb; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 120%;">
                                                        Request Sent Via <a href="signature1618.com" target="_blank" style="text-decoration: none; color: #FFD700;">Signature1618</a>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>