<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body style="color:#444444;font-size: 22px;font-style: normal;font-weight: bold;line-height: 150%;letter-spacing: normal;text-align: left;font-family:Georgia, 'Times New Roman', Times, serif;">
 
    <div style="text-align:center;text-align:center;max-width: 260px;margin: 0px auto;line-height:25px;">
        <h4 style="background:yellow;font-size: 21px;font-weight:100">New Document Received For Sign</h4>
    </div>
    
    <div style="max-width:600px;margin:0px auto;">
    <h3>Hi ,</h3>
    <h4>You have received a new document to approve. Please follow the below link to access the document file.</h4>

   
    
    <div style="margin-top:20px;text-align:center;">
    <a href="{{ env('APP_URL') }}/approve/?d={{$user_d['requestUID']}}&a={{$user_d['signerUID']}}" style="background:#2baadf;color:#ffffff;text-align:center;text-decoration:none;width:150px;margin:0px auto;font-size: 16px;padding: 15px;border-radius:4px;border:3px solid #0fde20;">Open Document</a>
    </div>
    
    <h3></h3>
    <hr style="border:1px solid #ddd;">
   <h3 style="font-size:16px;">Best regards<br>
        Team Signature1618
    </h3>  
    </div>

    <div style="background:#333333 none no-repeat center/cover;background-color: rgb(51, 51, 51);background-position-x: center;background-position-y: center;background-repeat: no-repeat;    background-image: none;background-size: cover;background-color: #333333;background-image: none;background-repeat: no-repeat;background-position: center;background-size: cover;border-top: 0;border-bottom: 0;padding-top: 45px;padding-bottom: 63px;margin-top:60px;max-width:700px;margin:60px auto;">
        
        
        <div style="text-align:center;">
          <p style="color:#fff;padding:0;margin:0;font-size:12px;font-style:italic;font-weight:normal;line-height:20px;margin-bottom:12px;font-family:arial;">Copyright © {{date('Y')}} Elysium Service. All rights reserved.<br>SIREN 885130682 RCS PARIS</p>
            <p style="color:#fff;padding:0;margin:0;font-size:12px;font-style:italic;font-weight:normal;line-height:20px;margin-bottom:12px;font-family:arial;">Our mailing address is:<br>7 rue Meyerbeer 75009</p>

        </div>
    </div> 
</body>
</html>