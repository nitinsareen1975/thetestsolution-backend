<div class="container" style="max-width: 680px; width: 100%; padding: 10px; font-family: Arial, Helvetica;text-align: center;">
    <div class="header" style="padding-bottom: 10px;">
        <img style="max-width: 300px;" src="{{ $logo_url }}">
        <p style="color: #4044af; font-size: 18px;">On Demand Clinical Laboratory Excellence</p>
        <hr style="max-width: 100px" />
        <h3 style="font-size: 18px">Your source of fast and reliable covid testing.<br />Where you want it when you want it with on-time results.</h3>
    </div>
    <div class="content">
        <div className="form-column" style="padding: 10px">
            <h4 style="font-size: 18px; font-weight: 400;">Please open the camera on your phone and scan the image below to pre-register for your test:</h4>
            <div style="margin: 16px 0; text-align: center;">
                <img src="{{ $qr_code }}" />
            </div>
        </div>
    </div>
    <div class="footer">
        <p>Please see the registration attendant for check-in. Once your testing is completed, you will receive your results at the email provided in the pre-registration within 2 to 3 hours. If results are not received within three hours, please call {{ $support_number }} and customer service will assist you.</p>
    </div>
</div>