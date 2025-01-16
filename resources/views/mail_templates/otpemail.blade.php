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
            <h1 style="font-size: 18px; color: #000000; margin: 0 0 10px 0;">Dear {{ rtrim($user_d['first_name']) }} {{ rtrim($user_d['last_name']) }},</h1>
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">To reset the password on your Signature1618 account kindly utilise this One-Time Passcode.</p>

            <!-- OTP Display -->
            <h2 style="font-size: 24px; color: #009c4a; text-align: center; margin: 20px 0;">One-Time Passcode:<br> {{$user_d['otp']}}</h2>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">Note: If you did not request this password reset or believe it to be in error, please ignore this email. Your password will remain unchanged.</p>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">For security reasons, this link will expire in 5 minutes. If you do not reset your password within this time frame, you will need to perform a new password reset request.</p>

            <br>
            <div style="color:#101112; direction:ltr; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; letter-spacing:0px; line-height:120%; text-align:left; mso-line-height-alt:19.2px;">
                <p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;">Request Sent Via <a href="https://signature1618.com" target="_blank" style="text-decoration: none; color: #FFD700;" rel="noopener">Signature1618</a></p>
        </div>
    </div>
</body>

</html>
