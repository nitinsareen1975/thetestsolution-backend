<div class="container" style="max-width: 650px; width: 100%; padding: 10px; font-family: Arial, Helvetica;">
    <div class="header" style="border-bottom: 2px solid #4e53ab; padding-bottom: 10px;">
        <img style="max-width: 150px;" src="/public/images/logo.jpg">
    </div>
    <div class="content">
        <p>Hi {{ $name }},</p>
        <p>Please use the link below to reset your password:</p>
        <p><strong><a href="{{ $resetLink }}" target="_blank">{{ $resetLink }}</a></strong></p>
        <p>
            Thank you,<br />
            Telestar Health team
        </p>
    </div>
</div>