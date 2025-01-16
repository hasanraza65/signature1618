<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">

<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="background-color: #ffffff; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
	<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff;">
        <tbody>
            <tr>
                <td>
                    <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #0018A8; color: #000000; width: 600px; margin: 0 auto;">
                        <tbody>
                            <tr>
                                <td style="font-weight: 400; text-align: left; padding-bottom: 20px; padding-left: 50px; padding-right: 50px; padding-top: 20px; vertical-align: top;">
                                    <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td style="width:100%;">
                                                                <div align="center" style="line-height:10px">
                                                                    <div style="max-width: 250px;"><img src="https://signature1618.app/backend_code/public/signaturelogo.png" style="display: block; height:60%; border: 0; width: 60%;"></div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>

					<table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
						<tbody>
							<tr>
								<td>
									<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border-left: 1px solid #ebebeb; border-right: 1px solid #ebebeb; color: #000000; width: 600px; margin: 0 auto;">
										<tbody>
											<tr>
												<td style="font-weight: 400; text-align: left; padding-bottom: 5px; padding-top: 20px; vertical-align: top;">
													<table width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
														<tr>
															<td>
																<h1 style="margin: 0; color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 400; line-height: 120%; text-align: left; margin-top: 0; margin-bottom: 0;">Dear {{ rtrim($user_d['first_name']) }} {{ rtrim($user_d['last_name']) }},</h1>
															</td>
														</tr>
													</table>
													<table width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
														<tr>
															<td>
                                                                <div style="color:#101112; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; line-height:120%; text-align:left;">
																	<p style="margin: 0;">{{$user_d['invited_member_name']}} has successfully joined {{$user_d['organization_name']}}'s team on Signature1618. {{$user_d['invited_member_name']}} now has access to Signature1618's Pro Plan features for enhanced collaboration within your organization.</p>
																</div>
															</td>
														</tr>
													</table>

													<table width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
														<tr>
															<td>
                                                                <table style="border: 1px solid #101112; border-collapse: collapse; width:100%; font-family: Arial, Helvetica, sans-serif; font-size: 16px;"> 
                                                                <p style="color:#101112; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; line-height:120%; text-align:left; margin: 0;"><strong>New Team Member:</strong><br></p>
                                                                <tr> 
                                                                    <td style="border: 1px solid #101112; padding: 8px;">Name</td>
                                                                    <td style="border: 1px solid #101112; padding: 8px;">    {{$user_d['invited_member_name']}}</td>
                                                                </tr>  
                                                            
                                                            
                                                                <tr>

                                                                    <td style="border: 1px solid #101112; padding: 8px;">Job Title</td>
                                                                    <td style="border: 1px solid #101112; padding: 8px;">    {{$user_d['job_title']}}</td>                                                               
                                                                </tr> 
                                                            
                                                            
                                                                <tr>
                                                            
                                                                    <td style="border: 1px solid #101112; padding: 8px;">Joined Date</td>
                                                                    <td style="border: 1px solid #101112; padding: 8px;"> {{ \App\Helpers\Common::dateFormat($user_d['joined_date']) }}</td>                                                 
                                                                </tr>                                                        
                                                            
                                                              </table>
															</td>
														</tr>
													</table>

													<table width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
														<tr>
															<td>
																<div style="color:#101112; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; line-height:120%; text-align:left;">
												
																	<p style="margin: 0;">Thank you for choosing Signature1618 for your electronic signature needs.</p>
																</div>
															</td>
														</tr>
													</table>
													<table width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
														<tr>
															<td>
																<div style="color:#101112; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; line-height:120%; text-align:left;">
																	<p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
																</div>
															</td>
														</tr>
													</table>
													<div style="height:10px; line-height:10px; font-size:1px;">&#8202;</div>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						</tbody>
					</table>
					  <table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #0018A8; padding:20px; color: #000000; width: 600px; margin: 0 auto;">
                                        <tbody>
                                            <tr>
                                                <td style="font-weight: 400; text-align: left; padding-bottom: 5px; padding-top: 5px; vertical-align: top;">
                                                    <table width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td>
                                                                <div style="color:#ebebeb; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:400; line-height:120%; text-align:center;">
                                                                    <p style="margin: 0;">Request Sent Via <a href="signature1618.com" target="_blank" style="text-decoration: none; color: #FFD700;" rel="noopener">Signature1618</a></p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>

				</td>
			</tr>
		</tbody>
	</table>
</body>

</html>