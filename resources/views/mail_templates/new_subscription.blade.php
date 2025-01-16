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
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">Thank you for subscribing to Signature1618! We are thrilled to have you on board. Your payment for the {{$user_d['plan_name']}} has been successfully processed. Here are your subscription details:</p>
            
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Plan:</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['plan_name']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Amount Paid:</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">${{$user_d['amount']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Subscription Period:</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['subscription_period']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Next Billing Date:</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{ \App\Helpers\Common::dateFormat($user_d['next_billing_date']) }}</td>
                </tr>
            </table>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">With Signature1618, you can now enjoy seamless and secure electronic signature service. Get started by logging into your account and accessing all the features your plan offers.</p>
			<br>
			<p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">If you have any questions or need assistance, our support team is here to help. You can reach us by opening a support ticket via your account.<br>Thank you for choosing Signature1618.</p>


            <!-- Login Button -->
            <a href="{{ env('APP_URL') }}" style="display: block; width: 50%; max-width: 300px; margin: 20px auto; padding: 12px; background-color: #009c4a; color: #ffffff; text-align: center; border-radius: 10px; text-decoration: none; font-size: 19px;">Login To Your Account</a>

            <br>
            <div style="color:#101112; direction:ltr; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; letter-spacing:0px; line-height:120%; text-align:left; mso-line-height-alt:19.2px;">
                <p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;"><strong>Signature1618</strong></p>
            <p style="margin: 16px 0 0;">16192 Coastal Highway, Lewes, Delaware 19958.</p>
        </div>
    </div>
</body>

</html>
