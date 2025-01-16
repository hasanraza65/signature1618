<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">

<head>
	<title>Email Template</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		* { box-sizing: border-box; }
		body { margin: 0; padding: 0; background-color: #ffffff; -webkit-text-size-adjust: none; text-size-adjust: none; }
		a[x-apple-data-detectors] { color: inherit !important; text-decoration: inherit !important; }
		p { line-height: inherit; }

		/* Desktop */
		.desktop_hide { display: none; }

		/* Mobile */
		@media (max-width: 620px) {
			.row-content { width: 100% !important; }
			.stack .column { width: 100%; display: block; }
			.mobile_hide { display: none !important; }
			.desktop_hide { display: block !important; }
		}
	</style>
</head>

<body style="background-color: #ffffff;">
	<!-- Outer Container -->
	<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff;">
		<tbody>
			<tr>
				<td>
					<!-- Header Row -->
					<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #0018A8; color: #ffffff; width: 600px; margin: 0 auto;">
						<tr>
							<td style="padding: 20px 50px;">
								<div align="center">
									<img src="https://signature1618.app/backend_code/public/signaturelogo.png" alt="Logo" style="max-width: 60%; display: block; height: auto;">
								</div>
							</td>
						</tr>
					</table>

					<!-- Main Content Row -->
					<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border: 1px solid #ebebeb; color: #000000; width: 600px; margin: 0 auto;">
						<tr>
							<td style="padding: 20px;">
								<!-- Greeting -->
								<h1 style="font-family: Arial, Helvetica, sans-serif; font-size: 18px; color: #000;">Dear {{ rtrim($user_d['sender_name']) }},</h1>

								<!-- Message -->
								<p style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; color: #101112;">
									This is to inform you that {{$user_d['signatory_a']}} has signed {{$user_d['file_name']}}. You can access the currently signed version of the document now.
								</p>

								<!-- Button -->
								<div align="center" style="margin: 20px 0;">
									<a href="{{ env('APP_URL') }}/signer/sign/?d={{$user_d['requestUID']}}&s={{$user_d['signer_unique_id']}}" target="_blank" style="background-color: #009c4a; color: #ffffff; text-decoration: none; display: inline-block; padding: 15px 25px; border-radius: 10px; font-size: 22px;">
										Access Dossier
									</a>
								</div>

								<!-- Additional Info -->
								<p style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; color: #101112;">
									A saved version is also available to you by creating a free trial account on Signature1618.
								</p>

								<!-- Closing -->
								<p style="font-family: Arial, Helvetica, sans-serif; font-size: 16px; color: #101112;">
									Best regards,<br>Signature1618 Support
								</p>
							</td>
						</tr>
					</table>

					<!-- Footer Row -->
					<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #0018A8; color: #ffffff; padding: 20px; width: 600px; margin: 0 auto;">
						<tr>
							<td align="center">
								<p style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #ffffff;">
									&copy; 2024 Signature1618. All rights reserved.
								</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
</body>

</html>
