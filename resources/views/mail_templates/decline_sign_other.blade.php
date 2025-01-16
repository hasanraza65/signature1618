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

            .row-2 .column-1 .block-2.paragraph_block td.pad>div,
            .row-2 .column-1 .block-3.paragraph_block td.pad>div,
            .row-3 .column-1 .block-1.paragraph_block td.pad>div {
                font-size: 14px !important;
            }
        }
    </style>
</head>

<body class="body" style="background-color: #ffffff; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
    <table class="nl-container" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff;">
        <tbody>
            <tr>
                <td>
                    <!-- Header Section -->
                    <table class="row row-1" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #0018A8; border-radius: 0; color: #000000; width: 600px; margin: 0 auto;" width="600">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" width="100%" style="padding: 20px 50px; vertical-align: top;">
                                                    <table class="image_block block-1" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                        <tr>
                                                            <td class="pad" style="width:100%;">
                                                                <div class="alignment" align="center">
                                                                    <div style="max-width: 250px;">
                                                                        <img src="https://signature1618.app/backend_code/public/signaturelogo.png" style="display: block; height: 60%; border: 0; width: 60%;">
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

                    <!-- Main Content Section -->
                    <table class="row row-2" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; border-left: 1px solid #ebebeb; border-right: 1px solid #ebebeb; color: #000000; width: 600px; margin: 0 auto;" width="600">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" width="100%" style="padding: 20px;">
                                                    <table class="heading_block block-1" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                        <tr>
                                                            <td class="pad">
                                                                <h1 style="margin: 0; color: #000000; font-family: Arial, Helvetica, sans-serif; font-size: 18px; font-weight: 400; line-height: 120%; text-align: left;">
                                                                    Dear {{ rtrim($user_d['user_first_name']) }} {{ rtrim($user_d['user_last_name']) }},
                                                                </h1>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table class="paragraph_block block-2" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                        <tr>
                                                            <td class="pad">
                                                                <div style="color:#101112; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:120%; text-align:left;">
                                                                    <p style="margin: 0;">{{$user_d['declined_by_first_name']}} {{$user_d['declined_by_last_name']}} has refused to sign {{$user_d['document_name']}}.<br>As such, the dossier is no longer available for signing. Kindly contact {{$user_d['organization_name']}} for more information.</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <table class="paragraph_block block-3" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                        <tr>
                                                            <td class="pad">
                                                                <div style="color:#101112; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:120%; text-align:left;">
                                                                    <p style="margin: 0;">Best regards,<br>Signature1618 Support</p>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <div class="spacer_block block-4" style="height:10px; line-height:10px; font-size:1px;">&#8202;</div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Footer Section -->
                    <table class="row row-3" align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="row-content stack" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #0018A8; padding:20px; border-radius: 0; color: #000000; width: 600px; margin: 0 auto;" width="600">
                                        <tbody>
                                            <tr>
                                                <td class="column column-1" width="100%" style="padding: 5px; vertical-align: top;">
                                                    <table class="paragraph_block block-1" width="100%" border="0" cellpadding="10" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                                        <tr>
                                                            <td class="pad">
                                                                <div style="color:#ebebeb; font-family:Arial, Helvetica, sans-serif; font-size:16px; text-align:center;">
                                                                    <p style="margin: 0;">Request Sent Via <a href="https://signature1618.com" style="color:#FFD700;">Signature1618</a></p>
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
