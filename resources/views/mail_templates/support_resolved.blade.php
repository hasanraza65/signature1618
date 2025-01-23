<!DOCTYPE html>
<html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">

<head>
	<title></title>
	<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
	<meta content="width=device-width, initial-scale=1.0" name="viewport" />
</head>

<body class="body"
	style="background-color: #ffffff; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
	<div style="max-width: 600px; margin: 0 auto; padding: 0px; border: 1px solid #ebebeb;">
		<table class="nl-container" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation"
			style="background-color: #ffffff;">
			<tbody>
				<tr>
					<td>
						<!-- Header Section (Logo centered) -->
						<table class="row row-1" align="center" width="100%" border="0" cellpadding="0" cellspacing="0"
							role="presentation">
							<tbody>
								<tr>
									<td>
										<table class="row-content stack" align="center" width="600" border="0"
											cellpadding="0" cellspacing="0"
											style="background-color: #0018A8; color: #000000; width: 600px; margin: 0 auto;">
											<tbody>
												<tr>
													<td class="column column-1" width="100%"
														style="font-weight: 400; text-align: left; padding: 20px 50px;">
														<table class="image_block block-1" width="100%" border="0"
															cellpadding="0" cellspacing="0" role="presentation">
															<tr>
																<td class="pad" style="width:100%;">
																	<div class="alignment" align="center"
																		style="line-height:10px">
																		<div style="max-width: 250px;">
																			<img src="https://signature1618.app/backend_code/public/signaturelogo.png"
																				style="display: block; height: 60%; border: 0; width: 60%;">
																		</div>
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

						<!-- Body Content Section -->
						<table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-2" width="100%">
							<tbody>
								<tr>
									<td>
										<table align="center" border="0" cellpadding="0" cellspacing="0"
											class="row-content stack" role="presentation"
											style="background-color: #ffffff; border-left: 1px solid #ebebeb; border-right: 1px solid #ebebeb; color: #000000; width: 600px; margin: 0 auto;">
											<tbody>
												<tr>
													<td class="column column-1" width="100%" style="padding-top: 20px;">
														<table border="0" cellpadding="10" cellspacing="0"
															class="heading_block block-1" role="presentation"
															width="100%">
															<tr>
																<td class="pad">
																	<h1
																		style="margin: 0; color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 400; text-align: left;">
																		Dear {{ rtrim($user_d['receiver_name']) }},</h1>
																</td>
															</tr>
														</table>
														<table border="0" cellpadding="10" cellspacing="0"
															class="paragraph_block block-2" role="presentation"
															width="100%">
															<tr>
																<td class="pad">
																	<div
																		style="color:#101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 120%; text-align: left;">
																		<p style="margin: 0;">We are pleased to inform
																			you that your support ticket has been
																			successfully resolved.</p>
																	</div>
																</td>
															</tr>
														</table>
														<table border="0" cellpadding="10" cellspacing="0"
															class="heading_block block-3" role="presentation"
															width="100%">
															<tr>
																<td class="pad">
																	<h1
																		style="margin: 0; color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 700; text-align: left;">
																		Ticket Details:</h1>
																</td>
															</tr>
														</table>
														<table border="0" cellpadding="10" cellspacing="0"
															class="list_block block-4" role="presentation" width="100%">
															<tr>
																<td class="pad">
																	<table
																		style="border: 1px solid #101112; border-collapse: collapse; width:100%; font-family: Arial, Helvetica, sans-serif; font-size: 16px;">
																		<tr>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				Ticket Status</td>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				{{$user_d['status']}}</td>
																		</tr>
																		<tr>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				Subject</td>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				{{$user_d['subject']}}</td>
																		</tr>
																		<tr>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				Date Submitted</td>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				{{$user_d['formattedDateSubmitted']}}
																			</td>
																		</tr>
																		<tr>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				Resolution Date</td>
																			<td
																				style="border: 1px solid #101112; padding:8px;">
																				{{ \Carbon\Carbon::now()->format('m/d/Y h:i A T') }}
																		</tr>
																	</table>
																</td>
															</tr>
														</table>
														
														<div class="spacer_block block-5"
															style="height:25px;line-height:25px;font-size:1px;"> </div>
														<table border="0" cellpadding="10" cellspacing="0"
															class="button_block block-6" role="presentation"
															width="100%">
															<tr>
																<td class="pad">
																	<div align="center">
																		<a href="{{ env('APP_URL') }}/support"
																			style="font-family: Arial, Helvetica, sans-serif; background-color: #009c4a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View
																			Your Support Ticket</a>
																	</div>
																</td>
															</tr>
														</table>
														<div class="spacer_block block-7"
															style="height:25px;line-height:25px;font-size:1px;"> </div>
													</td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>
							</tbody>
						</table>

		
														<div style="padding: 20px; font-size: 16px; line-height: 1.5; font-family: Arial, Helvetica, sans-serif;">
															<p style="margin: 0; margin-bottom: 16px;">Thank you
																for choosing Signature1618. We appreciate your
																patience and trust in our service.</p>
															<p style="margin: 0;">Best
																regards,<br />Signature1618 Support</p>
														</div>

						<!-- Footer Section (remains unchanged) -->
						<div
							style="background-color: #0018A8; color: #ffffff; padding: 20px; text-align: center; font-size: 16px; line-height: 1.5; font-family: Arial, Helvetica, sans-serif;">
							<p style="margin: 0;">
								Request Sent Via <a href="https://signature1618.com/"
									style="color: #FFD700; text-decoration: none;">Signature1618</a>
							</p>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</body>

</html>