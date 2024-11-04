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
            <p style="font-size: 16px; line-height: 1.5; margin: 0 0 10px 0;">You have received a new signature request from {{ $user_d['company_name'] }}.</p>

            <!-- Access Dossier Button -->
            <a href="{{ env('APP_URL') }}/signer/sign/?d={{$user_d['requestUID']}}&s={{$user_d['signerUID']}}" target="_blank" style="display: block; width: 50%; max-width: 300px; margin: 20px auto; padding: 12px; background-color: #009c4a; color: #ffffff; text-align: center; border-radius: 10px; text-decoration: none; font-size: 19px;">Access Dossier</a>

            <!-- Information Table -->
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Dossier</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{ $user_d['file_name'] }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Requested by</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{ $user_d['company_name'] }}</td>
                </tr>
                <tr>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">Expiration Date</td>
                    <td style="border: 1px solid #101112; padding: 8px; font-size: 16px;">{{ \App\Helpers\Common::dateFormat($user_d['expiry_date']) }}</td>
                </tr>
            </table>
            
            <br>
            
            <table class="paragraph_block block-8" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;">
														<tr>
															<td class="pad">
																<div style="color:#101112;direction:ltr;font-family:Arial, Helvetica, sans-serif;font-size:16px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;mso-line-height-alt:19.2px;">
																	<p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
																</div>
															</td>
														</tr>
													</table>
            
        </div>
        
        <!-- Footer -->
        <div style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5;">
            <p style="margin: 0;">Request Sent Via <strong>Signature1618</strong></p>
            <p style="margin: 16px 0 0;">Your information is protected by Signature1618's advanced encryption, ensuring the highest level of security for your documents. If you have any questions or need assistance signing, please visit our <a href="https://signature1618.com/help-center/" target="_blank" style="color: #FFD700; text-decoration: none;" rel="noopener">support center</a>.</p>
        </div>
    </div>
</body>

</html>
