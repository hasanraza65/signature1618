<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: Arial, Helvetica, sans-serif; color: #101112;">
    <div style="max-width: 600px; margin: 0 auto; padding: 0px; border: 1px solid #ebebeb;">
        <!-- Header with Logo -->
        <div style="background-color: #0018A8; padding: 20px; text-align: center;">
            <img src="https://signature1618.app/backend_code/public/signaturelogo.png" alt="Company Logo" style="max-width: 150px;">
        </div>

        <!-- Email Content -->
        <div style="padding: 20px;">
            <h1 style="font-size: 18px; color: #000000; margin: 0 0 10px 0;">Dear {{$user_d['receiver_name']}},</h1>
            <br>
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">
                This is a friendly reminder to sign the dossier from {{$user_d['company_name']}}. The signature request was sent to you earlier, but we have not yet received your response.
            </p>

            <!-- Access Dossier Button -->
            <a href="{{ env('APP_URL') }}/signer/sign/?d={{$user_d['requestUID']}}&s={{$user_d['signerUID']}}" style="display: block; width: 50%; max-width: 300px; margin: 20px auto; padding: 12px; background-color: #009c4a; color: #ffffff; text-align: center; border-radius: 10px; text-decoration: none; font-size: 19px;">Access Dossier</a>

            <!-- Dossier Details Table -->
            <p style="font-size: 16px; line-height: 1.5; margin-top: 20px;">Here are the details of the dossier:</p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Dossier</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['file_name']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Requested by</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['company_name']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Expiration Date</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{ \App\Helpers\Common::onlyDateFormat($user_d['expiry_date']) }}</td>
                </tr>
            </table>

            <!-- Closing Message -->
            <p style="font-size: 16px; line-height: 1.5; margin-top: 20px;">
                Best regards,<br>Signature1618 Support
            </p>
        </div>

        <!-- Footer -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;">
                Request Sent Via <a href="https://signature1618.com/" style="color: #FFD700; text-decoration: none;"> Signature1618</a>
            </p>
            <p style="margin: 0; font-size: 14px; margin-top: 10px;">
                Your information is protected by Signature1618's advanced encryption, ensuring the highest level of security for your documents. If you have any questions or need assistance signing, please visit our support center.
            </p>
        </div>
    </div>
</body>

</html>
