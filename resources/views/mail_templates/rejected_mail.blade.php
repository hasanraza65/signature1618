<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin: 0; padding: 0; background-color: #ffffff; font-family: Arial, Helvetica, sans-serif; color: #101112;">
    <div style="max-width: 600px; margin: 0 auto; padding: 0; border: 1px solid #ebebeb;">
        <!-- Header with Logo -->
        <div style="background-color: #0018A8; padding: 20px; text-align: center;">
            <img src="https://signature1618.app/backend_code/public/signaturelogo.png" alt="Company Logo" style="max-width: 150px;">
        </div>

        <!-- Email Content -->
        <div style="padding: 20px;">
            <h1 style="font-size: 18px; color: #000000; margin: 0 0 10px 0;">Dear {{ rtrim($user_d['sender_name']) }},</h1><br>
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">
                {{$user_d['approver_name']}} has refused approval of {{$user_d['file_name']}}.
            </p>
            <p style="font-size: 16px; line-height: 1.5; margin: 10px 0;">
                As such, the dossier is no longer valid. You may access additional information about the dossier by consulting the dossier's page.
            </p>

            <!-- Green Button -->
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ env('APP_URL') }}/request-details/?r={{$user_d['requestUID']}}" style="background-color: red; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;">
                    Access Dossier
                </a>
            </div>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">
                Best regards,<br>
                Signature1618 Support
            </p>
        </div>

        <!-- Footer with Signature1618 Info -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;">
                Request Sent Via <a href="https://signature1618.com/" style="color: #FFD700; text-decoration: none;">Signature1618</a>
            </p>
        </div>
    </div>
</body>

</html>
