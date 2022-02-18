<style>
    table,
    table tr,
    table th,
    table td, table th {
        border: none;
        text-align: left !important;
    }
</style>
<div style="max-width: 767px;">
<p>Hello, {{ $name }}</p>
<p>You have been pre-registered for a screening.</p>
<p>We recommend that you print this email prior to coming to the testing site to make scanning easier. This will be scanned by the nurse before you are tested and will be used to track back your results.</p>
<p style="text-align: center;">{!! $qrCode !!}</p>
<p>Code: {{ $confirmationCode }}</p>
<p>The code is valid only for you and cannot be used by anyone else.</p>
<p>The laboratory and appointment information is as follows:</p>
<p>
<table cellspacing="0" cellpadding="0">
    <tr>
        <td>Date: </td>
        <th>{{ $scheduleDate }}</th>
    </tr>
    <tr>
        <td>Time: </td>
        <th>{{ $scheduleTime }}</th>
    </tr>
    <tr>
        <td>Name: </td>
        <th>{{ $labName }}</th>
    </tr>
    <tr>
        <td>Address: </td>
        <th>{{ $labAddress }}</th>
    </tr>
    <tr>
        <td>Map Link: </td>
        <th>&nbsp;<a href="{{ $mapsLink }}" target="_blank">Click Here</a></th>
    </tr>
</table>
</p>
<p>
    Thank you,<br />
    The Test Solution team
</p>
</div>