<div class="container" style="max-width: 650px; width: 100%; padding: 10px; font-family: Arial, Helvetica;">
    <div class="header" style="border-bottom: 2px solid #4e53ab; padding-bottom: 10px;">
        <img style="max-width: 150px;" src="/public/images/logo.jpg">
    </div>
    <div class="content">
        <p>Hi {{ $name }},</p>
        <p>You have been pre-registered for a screening.</p>
        <p>We recommend that you print this email prior to coming to the testing site to make scanning easier. This will be scanned by the nurse before you are tested and will be used to track back your results.</p>
        <p style="text-align: center;">{!! $qrCode !!}</p>
        <p>Code: {{ $confirmationCode }}</p>
        <p>The code is valid only for you and cannot be used by anyone else.</p>
        <p>The laboratory and appointment information is as follows:</p>
        <p>
        <table cellspacing="0" cellpadding="0">
            <tr>
                <td>Date/Time: </td>
                <th style="text-align: left">{{ $scheduleDate }} @ {{ $scheduleTime }}</th>
            </tr>
            <tr>
                <td>Lab Name: </td>
                <th style="text-align: left">{{ $labName }}</th>
            </tr>
            <tr>
                <td>Address: </td>
                <th style="text-align: left">{{ $labAddress }}</th>
            </tr>
            <tr>
                <td>Map Link: </td>
                <th style="text-align: left">&nbsp;<a href="{{ $mapsLink }}" target="_blank">Click Here</a></th>
            </tr>
        </table>
        </p>
        <p>
            Thank you,<br />
            Telestar Health team
        </p>
    </div>
</div>