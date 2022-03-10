<div class="container" style="max-width: 650px; width: 100%; padding: 10px; font-family: Arial, Helvetica;">
    <div class="header" style="border-bottom: 2px solid #4e53ab; padding-bottom: 10px;">
        <img style="max-width: 150px;" src="/public/images/logo.jpg">
    </div>
    <div class="content">
        <p>Hi {{ $name }},</p>
        <p>This message is a notification letting you know that you have clinical laboratory test results available.</p>
        <p><a href="{{ $resultsLink }}" target="_blank">Click Here</a> to view your results.</p>
        <p>
            Thank you,<br />
            Telestar Health team
        </p>
    </div>
</div>