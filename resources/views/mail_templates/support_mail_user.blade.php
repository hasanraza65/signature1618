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
            <h1 style="font-size: 18px; color: #000000; margin: 0 0 10px 0;">Dear {{ rtrim($user_d['receiver_name']) }},</h1>
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">We have received your support ticket and our team is currently handling your request.</p>
            
            <h4>Ticket Details:</h4>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Ticket Status</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['status']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Subject</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{$user_d['subject']}}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Date Submitted</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{ \App\Helpers\Common::onlyDateFormat($user_d['submission_date']) }}</td>
                </tr>
               
            </table>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">Our support team is dedicated to resolving your issue as quickly as possible. You can expect a response within 48 hours.</p>
			<br>
			<p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">If you need to provide additional information or have any questions regarding your ticket, please reply to this email or visit your support ticket dashboard:</p>


            <!-- Login Button -->
            <a href="{{ env('APP_URL') }}/support" style="display: block; width: 50%; max-width: 300px; margin: 20px auto; padding: 12px; background-color: #009c4a; color: #ffffff; text-align: center; border-radius: 10px; text-decoration: none; font-size: 19px;">View Your Request</a>

            <br>
            <div style="color:#101112; direction:ltr; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; letter-spacing:0px; line-height:120%; text-align:left; mso-line-height-alt:19.2px;">
                <p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
            </div>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">We appreciate your patience and will keep you updated on the progress of your ticket.</p>
			<br>
			<p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">Thank you for using Signature1618.</p>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; ">
            <p style="margin: 0;"><a href="signature1618.com" target="_blank" style="text-decoration: none; color: #FFD700;" rel="noopener" bis_size="{&quot;x&quot;:874,&quot;y&quot;:753,&quot;w&quot;:104,&quot;h&quot;:18,&quot;abs_x&quot;:874,&quot;abs_y&quot;:812}">Signature1618</a></p>
            <p style="margin: 16px 0 0;">16192 Coastal Highway, Lewes, Delaware 19958.</p>
        </div>
    </div>
</body>

</html>
