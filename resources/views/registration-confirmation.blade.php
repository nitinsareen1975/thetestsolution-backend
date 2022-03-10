<div class="container" style="max-width: 650px; width: 100%; padding: 10px; font-family: Arial, Helvetica;">
    <div class="header" style="border-bottom: 2px solid #4e53ab; padding-bottom: 10px;">
        <img style="max-width: 150px;" src="{{ $logoUrl }}">
    </div>
    <div class="content">
        <p>Hi {{ $name }},</p>
        <p>Your account has been successfully created. Please use the credentials below to login to our portal:</p>
        <p>
        <table cellspacing="0" cellpadding="0">
            <tr>
                <th style="text-align: left">App URL: </th>
                <td style="text-align: left">{{ $appUrl }}</td>
            </tr>
            <tr>
                <th style="text-align: left">Username: </th>
                <td style="text-align: left">{{ $username }}</td>
            </tr>
            <tr>
                <th style="text-align: left">Password: </th>
                <td style="text-align: left">{{ $password }}</td>
            </tr>
        </table>
        </p>
        <p>
            Thank you,<br />
            Telestar Health team
        </p>
    </div>
</div>