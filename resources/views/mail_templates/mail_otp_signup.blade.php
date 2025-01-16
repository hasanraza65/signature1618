<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <title></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch><o:AllowPNG/></o:OfficeDocumentSettings></xml><![endif]-->
</head>

<body style="margin: 0; padding: 0; background-color: #ffffff; -webkit-text-size-adjust: none; text-size-adjust: none;">
    <table class="nl-container" width="100%" role="presentation" style="background-color: #ffffff;">
        <tbody>
            <tr>
                <td>
                    <!-- Header Row -->
                    <table class="row row-1" align="center" width="100%" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" role="presentation" style="background-color: #0018A8; width: 600px; margin: 0 auto; color: #000000;">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" style="padding: 20px 50px;">
                                                    <table class="image_block block-1" width="100%" role="presentation">
                                                        <tr>
                                                            <td style="width:100%;">
                                                                <div align="center">
                                                                    <img src="https://signature1618.app/backend_code/public/signaturelogo.png" style="display: block; height: 60%; width: 60%; border: 0;">
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

                    <!-- Body Row -->
                    <table class="row row-2" align="center" width="100%" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" role="presentation" style="background-color: #ffffff; border-left: 1px solid #ebebeb; border-right: 1px solid #ebebeb; width: 600px; margin: 0 auto;">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" style="padding-top: 20px;">
                                                    <!-- Greeting Block -->
                                                    <table class="heading_block block-1" width="100%" role="presentation">
                                                        <tr>
                                                            <td>
                                                                <h1 style="margin: 0; color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 18px;">Dear {{ rtrim($user_d['user_name']) }},</h1>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <!-- Message Content -->
                                                    <table class="paragraph_block block-2" width="100%" role="presentation">
                                                        <tr>
                                                            <td>
                                                                <p style="margin: 0; color: #101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px;">Welcome to Signature1618, here is your One-Time Passcode to validate your account. Kindly input this One-Time Passcode where prompted.</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <!-- Spacer -->
                                                    <div style="height:25px; line-height:25px; font-size:1px;">&#8202;</div>
                                                    <!-- OTP Display -->
                                                    <table class="heading_block block-4" width="100%" role="presentation">
                                                        <tr>
                                                            <td>
                                                                <h1 style="margin: 0; color: #111111; font-family: Arial, Helvetica, sans-serif; font-size: 28px; text-align: center;">{{$user_d['otp']}}</h1>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <!-- Spacer -->
                                                    <div style="height:15px; line-height:15px; font-size:1px;">&#8202;</div>
                                                    <!-- Additional Information -->
                                                    <table class="paragraph_block block-6" width="100%" role="presentation">
                                                        <tr>
                                                            <td>
                                                                <p style="margin: 0; color: #101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px;">Ensure to subscribe to one of our paid plans to enjoy the full features of Signature1618.</p>
                                                                <p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <!-- Spacer -->
                                                    <div style="height:10px; line-height:10px; font-size:1px;">&#8202;</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Footer Row -->
                    <table class="row row-3" align="center" width="100%" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" role="presentation" style="background-color: #0018A8; padding:20px; width: 600px; margin: 0 auto; color: #000000;">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" style="padding: 5px;">
                                                    <table class="paragraph_block block-1" width="100%" role="presentation">
                                                        <tr>
                                                            <td>
                                                                <p style="margin: 0; color:#ebebeb; font-family: Arial, Helvetica, sans-serif; font-size:16px; text-align:center;">Request Sent Via <a href="https://signature1618.com" target="_blank" style="text-decoration: none; color: #FFD700;" rel="noopener">Signature1618</a></p>
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

                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>