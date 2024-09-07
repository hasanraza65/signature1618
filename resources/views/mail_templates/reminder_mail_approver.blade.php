<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="en">

<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
        }

        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: inherit !important;
        }

        #MessageViewBody a {
            color: inherit;
            text-decoration: none;
        }

        p {
            line-height: inherit;
        }

        .desktop_hide,
        .desktop_hide table {
            mso-hide: all;
            display: none;
            max-height: 0px;
            overflow: hidden;
        }

        .image_block img+div {
            display: none;
        }

        @media (max-width:620px) {
            .mobile_hide {
                display: none;
            }

            .row-content {
                width: 100% !important;
            }

            .stack .column {
                width: 100%;
                display: block;
            }

            .mobile_hide {
                min-height: 0;
                max-height: 0;
                max-width: 0;
                overflow: hidden;
                font-size: 0px;
            }

            .desktop_hide,
            .desktop_hide table {
                display: table !important;
                max-height: none !important;
            }
        }
    </style>
</head>

<body class="body" style="background-color: #ffffff; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
    <table class="nl-container" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff;">
        <tbody>
            <tr>
                <td>
                    <table class="row row-1" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #244BED; color: #000000; width: 600px; margin: 0 auto;" width="600">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" width="100%" style="font-weight: 400; text-align: left; padding-bottom: 20px; padding-left: 50px; padding-right: 50px; padding-top: 20px; vertical-align: top;">
                                                    <table class="image_block block-1" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td class="pad" style="width:100%;">
                                                                <div class="alignment" align="center" style="line-height:10px">
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
                    <table class="row row-2" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #ffffff; border-left: 1px solid #ebebeb; border-right: 1px solid #ebebeb; color: #000000; width: 600px; margin: 0 auto;" width="600">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" width="100%" style="font-weight: 400; text-align: left; padding-bottom: 5px; padding-top: 20px; vertical-align: top;">
                                                    <table class="heading_block block-1" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td class="pad">
                                                                <h1 style="margin: 0; color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 400; letter-spacing: normal; line-height: 120%; text-align: left;">
                                                                    <span class="tinyMce-placeholder">Dear {{$user_d['receiver_name']}},</span>
                                                                </h1>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table class="paragraph_block block-2" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td class="pad">
                                                                <div style="color:#101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 400; letter-spacing: 0px; line-height: 120%; text-align: left;">
                                                                    <p style="margin: 0;">This is a friendly reminder to approve the dossier from {{$user_d['company_name']}}. The approval request was sent to you earlier, but we have not yet received your response.</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table class="button_block block-4" align="center" width="350px" border="0" cellpadding="10" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td class="pad">
                                                                <div class="alignment" align="center">
                                                                    <a href="{{ env('APP_URL') }}/approve/?d={{$user_d['requestUID']}}&a={{$user_d['signerUID']}}" target="_blank" style="background-color:#009c4a; border-radius:10px; color:#ffffff; display:block; font-family: Arial, Helvetica, sans-serif; font-size:22px; font-weight:400; padding-bottom:5px; padding-top:5px; text-align:center; text-decoration:none; width:65%;"><span style="padding-left:20px; padding-right:20px; font-size:22px; display:inline-block; letter-spacing:normal;"><span style="line-height: 44px;">Access Dossier</span></span></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table class="paragraph_block block-3" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td class="pad">
                                                                <table style="border: 1px solid #101112; border-collapse: collapse; width: 100%; font-family: Arial, Helvetica, sans-serif; font-size: 16px;">
                                                                    <tr>
                                                                        <td style="border: 1px solid #101112; padding: 8px;">Dossier</td>
                                                                        <td style="border: 1px solid #101112; padding: 8px;">{{$user_d['file_name']}}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="border: 1px solid #101112; padding: 8px;">Requested by</td>
                                                                        <td style="border: 1px solid #101112; padding: 8px;">{{$user_d['company_name']}}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="border: 1px solid #101112; padding: 8px;">Expiration Date</td>
                                                                        <td style="border: 1px solid #101112; padding: 8px;">{{$user_d['expiry_date']}}</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table class="paragraph_block block-8" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td class="pad">
                                                                <div style="color:#101112; font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 400; letter-spacing: 0px; line-height: 120%; text-align: left;">
                                                                    <p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <div class="spacer_block block-5" style="height:10px;line-height:10px;font-size:1px;">&#8202;</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="row row-3" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color: #244BED; color: #000000; width: 600px; margin: 0 auto;" width="600">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" width="100%" style="font-weight: 400; text-align: left; padding-bottom: 5px; padding-top: 5px; vertical-align: top;">
                                                    <table class="paragraph_block block-1" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation">
                                                        <tr>
                                                            <td class="pad">
                                                                <div style="color:#ebebeb; font-family: Arial, Helvetica, sans-serif; font-size: 16px; font-weight: 400; letter-spacing: 0px; line-height: 120%; text-align: center;">
                                                                    <p style="margin: 0; margin-bottom: 16px;">Request Sent Via <a href="signature1618.com" target="_blank" style="text-decoration: none; color: #FFD700;" rel="noopener">Signature1618</a></p>
                                                                    <p style="margin: 0;">If you are having trouble approving the document, please visit this link from an internet connected computer. {{ env('APP_URL') }}/signer/sign/?d={{$user_d['requestUID']}}&s={{$user_d['signerUID']}}.</p>
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
