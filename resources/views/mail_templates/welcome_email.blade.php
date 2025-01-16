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
        <div style="padding: 20px; background-color: #f8f4f4;">
            <h1 style="font-size: 18px; color: #000000; margin: 0 0 10px 0;">Dear {{ rtrim($user_d['first_name']) }} {{ rtrim($user_d['last_name']) }},</h1><br>
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">
                We are delighted to have you on board. Signature1618 is designed to simplify and secure your document signing process, making it more efficient than ever.
            </p>

            <!-- Button -->
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ env('APP_URL') }}" style="background-color: #009c4a; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;">
                    Get Started
                </a>
            </div>

            <p style="font-size: 16px; line-height: 1.5; margin: 10px 0;">
                Our platform allows you to:
            </p>
            <ul style="font-size: 16px; line-height: 1.5; margin: 10px 0 20px 20px; padding: 0;">
                <li>Create and manage electronic signatures effortlessly</li>
                <li>Sign documents securely from any location</li>
                <li>Streamline your workflow and save valuable time</li>
            </ul>

            <p style="font-size: 16px; line-height: 1.5; margin: 10px 0;">
                Explore the powerful features of Signature1618 and start your free trial today!
            </p>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">
                Thank you for choosing Signature1618. We look forward to helping you transform your document signing experience.
            </p>

            <p style="font-size: 16px; line-height: 1.5; margin: 20px 0 10px 0;">
                Best regards,<br>
                Signature1618 Team
            </p>
        </div>

        <!-- Colored Background Section with Address -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;">Signature1618</p>
            <p style="margin: 0;">16192 Coastal Highway, Lewes, Delaware 19958</p>
        </div>
    </div>
</body>

</html>
