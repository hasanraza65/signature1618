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
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">
                Unfortunately, your subscription has failed to renew. To avoid interruptions to your document signing, kindly update your payment information or upgrade your subscription now.
            </p>

            <!-- Renew Now Button -->
            <a href="https://signature1618.app" style="display: block; width: 50%; max-width: 300px; margin: 20px auto; padding: 12px; background-color: #009c4a; color: #ffffff; text-align: center; border-radius: 10px; text-decoration: none; font-size: 19px;">Renew Now</a>

            <!-- Subscription Details Table -->
            <p style="font-size: 16px; line-height: 1.5; margin-top: 20px;">Here are the details of your subscription:</p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Plan</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['plan_name']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Status</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['substatus']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">End Date</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{ \App\Helpers\Common::onlyDateFormat($user_d['end_date']) }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Price</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">${{$user_d['price']}}</td>
                </tr>
            </table>

            <!-- Closing Message -->
            <p style="font-size: 16px; line-height: 1.5; margin-top: 20px;">
                If you have any questions or need assistance, please don't hesitate to contact our support team. Thank you for being a valued client of Signature1618.
            </p>
            <br>
            <p style="color:#101112; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:120%; text-align:left;">
                Best regards,<br>Signature1618 Support
            </p>
        </div>

        <!-- Footer -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;">Email Sent Via Signature1618</p>
        </div>
    </div>
</body>

</html>
