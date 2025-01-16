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
            <h1 style="font-size: 18px; color: #000000; margin: 0 0 10px 0;">Dear {{ rtrim($user_d['receiver_name']) }},</h1><br>
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">
                You have been invited to join {{$user_d['senderName']}}. 
            </p>
            <p>
            Use Signature1618 today to process your electronic signature request and manage your documents.
            </p>

            <!-- Button -->
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ env('APP_URL') }}/?join_team={{$user_d['unique_id']}}" style="background-color: #0018A8; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;">
                    Join Team
                </a>
            </div>

            <p style="font-size: 16px; line-height: 1.5; margin: 10px 0;">
                To accept the invitation:
                <br>1. Create an account on Signature1618.com with your current email.
                <br>2. Accept the invitation on the Teams page on your Signature1618 account.
            </p>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">
                We look forward to having you on board and experiencing the benefits of our platform!
            </p>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">
                Best regards,<br>
                {{$user_d['senderName']}}
            </p>
        </div>

        <!-- Colored Background with Link -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;">
                Request Sent Via <a href="https://signature1618.com/" style="color: #FFD700; text-decoration: none;">Signature1618</a>
            </p>
        </div>

    </div>
</body>

</html>
