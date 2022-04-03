<style>
    .reportwrap {
        max-width: 990px;
        width: 100%;
        margin: auto;
        display: block;
        font-family: sans-serif;
    }

    .reportwrap table td {
        font-size: 15px;
        color: #000;
        line-height: 23px;
    }

    .reportTable {
        padding: 0px 10px;
    }

    .reportTable .ant-page-header-content {
        padding-top: 0px;
    }

    .reportTable .ant-page-header-content tr {
        border: 1px solid #000;
    }

    .reportTable .ant-page-header-content tr td {
        padding: 5px 10px;
    }

    .resultTable th,
    .resultTable td {
        padding: 8px;
        border: 1px solid #e2e2e2;
        font-size: 15px;
        text-align: left;
    }

    .commentBlock {
        text-align: left;
    }

    .commentBlock p,
    .commentBlock li {
        font-size: 10px;
        margin-bottom: 2px;
        color: #000;
    }

    .commentBlock .content .list-unstyled {
        list-style: none;
        margin: 0px;
        padding-left: 27px;
    }

    .commentBlock .content .list-unstyled li {
        padding-left: 10px;
        position: relative;
    }

    .commentBlock .content .list-unstyled li:before {
        content: '*';
        position: absolute;
        left: 0px;
        top: 0px;
        color: red;
    }

    .commentBlock .content ol,
    .commentBlock .content ul {
        margin-bottom: 5px;
    }

    .commentResult th,
    .commentResult td {
        padding: 4px 10px;
        border: 1px solid #000;
        font-size: 12px !important;
        font-weight: 600;
        color: #000;
    }

    .reportFooter {
        padding: 5px 22px;
        text-align: left;
        font-size: 15px;
    }

    .reportFooter table {
        max-width: 100%;
        width: 100%;
        border: 1px solid #ddd;
        border-spacing: 5px;
        border-collapse: inherit;
        margin-top: 10px;
    }

    .reportFooter table th {
        border-bottom: 1px solid #ddd;
        color: #000;
        padding-bottom: 5px;
        text-align: left;
    }

    .reportDetailsTable td {
        border: 1px solid #e4e4e4;
        padding: 8px;
    }

    .reportDetailsTable {
        border-spacing: 0px;
        margin-bottom: 15px;
    }

    .descriptionTable td {
        border: 1px solid #e4e4e4;
        padding: 5px;
    }

    .reportwrap table .list {
        padding: 5px;
    }

    .reportDetailsTable td table td {
        border: none;
        padding: 0px;
    }

    @page {
        margin: 10px;
    }

    body {
        margin: 10px;
        font-family: Arial, Helvetica, sans-serif;
    }

    .signature {
        position: fixed;
        bottom: 40px;
        left: 0cm;
        right: 15px;
        text-align: right;
        line-height: 28px;
        font-style: italic;
        font-size: 15px;
    }

    footer {
        position: fixed;
        bottom: 0cm;
        left: 0cm;
        right: 0cm;
        height: 32px;

        border-top: 1px solid #222;
        /** Extra personal styles **/
        text-align: center;
        line-height: 28px;
        font-size: 14px;
        font-style: italic;
    }
</style>
<div class="reportwrap">
    <div class="reportwrap-print">
        <table style="max-width: 100%;width:100%;margin:5px 0px 5px;min-height: 100px;">
            <tr>
                <td style="width: 50%">
                    <div xs={12} style="text-align: left;">
                        <img src="{{ $report_logo }}" alt="Logo" style="width: 100%;max-width:150px;max-height:55px;" />
                    </div>
                </td>
                <td style="width: 50%">
                    <div style="float: right; text-align:right">
                        <p>
                            {{ $report_lab_name }}<br />
                            {{ $report_lab_street }}<br />
                            {{ $report_lab_city }}, {{ $report_lab_state }}, {{ $report_lab_zip }}
                        </p>
                    </div>
                </td>
            </tr>
        </table>
        <br />
        <table style="max-width:100%;width:100%;margin-bottom: 15px;">
            <tr>
                <td style="width: 100%;background: #ebebeb;text-align: center;">
                    <div class="clia" style="text-align: center;padding: 10px 10px;font-size: 18px;font-weight: 600;display: inline-flex;justify-content: center;">
                        <strong>CLIA:</strong> {{ $report_licence_number }}
                    </div>
                </td>
                <td style="max-width: 110px;">
                    <div class="scan">
                        <img style="width: 100px;" src="{{ $report_qrcode }}" />
                    </div>
                </td>
            </tr>
        </table>

        <table style="max-width:100%;width:100%;" class="reportDetailsTable">
            <tr>
                <td colspan="2" style="font-weight: 600;text-transform: uppercase;font-size: 14px;background: #ebebeb;padding: 10px;">Clinical Laboratory Report</td>
            </tr>
            <tr>
                <td colspan="2">
                    <table style="max-width:100%;width:100%;">
                        <td><strong>Account#</strong> {{ $report_id }}</td>
                        <td>&nbsp;</td>
                        @if($report_identifier !='')
                            @if($report_identifier_type =='Passport')
                            <td style="text-align: right;"><strong>Passport#</strong> {{ $report_identifier }}</td>
                            @else
                            <td style="text-align: right;"><strong>DL#</strong> {{ $report_identifier }}</td>
                            @endif
                        @else
                            <td></td>
                        @endif
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width: 50%;">
                    <div class="labAddress">
                        {{ $report_lab_name }}<br />
                        {{ $report_lab_street }}<br />
                        {{ $report_lab_city }}, {{ $report_lab_state }}, {{ $report_lab_zip }}
                    </div>
                    <div class="labTel">
                        <strong>TEL:</strong> {{ $report_lab_phone }}
                        <br>
                        <strong>EMAIL:</strong> {{ $report_lab_email }}
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="patient-info">
                        <div class="patientName">
                            <strong>PATIENT:</strong> {{ $report_firstname }} {{ $report_lastname }}
                        </div>
                        <div class="pbottomInfo">
                            <div class="dob">
                                <strong>DOB:</strong> {{ $report_dob }}
                            </div>
                            <div class="sex">
                                <strong>SEX:</strong> {{ $report_gender }}
                            </div>
                        </div>
                        <!-- <div><strong>COLLECTED:</strong> {{ date('m/d/Y', strtotime($report_specimen_collection_date)) }}</div> -->
                        <div><strong>REPORTED:</strong> {{ date('m/d/Y', strtotime($report_result_date)) }}</div>
                        <div><strong>GROUP EVENT:</strong> {{ $report_event_name }}</div>
                    </div>
                </td>
            </tr>

        </table>
        <div class="descriptionTable">
            <table cellspacing="0" class="resultTable" style="max-width: 100%; width: 100%; text-align: left ">
                <thead style=" background: #ebebeb; ">
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 50%;">Result</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="list">
                                <strong>Method:</strong> {{ $report_test_type_name }}
                            </div>
                            @if($report_test_procedure !='')
                            <div class="list">
                                <strong>Procedure:</strong> {{ $report_test_procedure }}
                            </div>
                            @endif
                            <!-- <div class="list">
                                <strong>Specimen Type:</strong> {{ $report_test_type_method }}
                            </div> -->
                        </td>
                        <td>
                            <div class="list">
                                <!-- <strong>{{ $report_result }}</strong>
                                @if($report_fi_test_type !='')
                                for {{ $report_fi_test_type }}
                                @endif -->
                            </div>

                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="content" style="font-size: 14px;    line-height: 24px;">
            <p>This test has been performed following {{ $report_test_type_name }} methodology. This test has been authorized by FDA. This test has been validated in accordance with the FDA's Guidance Document (Policy for diagnostics testing in laboratories certified to perform testing under CLIA waiver prior to Emergency Use Authorization for coronavirus Disease-2019 during the public health Emergency) </p>
        </div>
        <table style="max-width:100%;width:100%;">
            <tr>
                <td>
                    <div class="doctorDetails">
                        <div class="signature">

                        </div>
                    </div>
                </td>
                <td style="text-align: right;">
                    <!-- <div class="labStamp" style="text-align: right;display: inline-block;">
                        Medical Director: {{ $report_concerned_person_name }}<br />
                        NPI: {{ $report_npi }}
                    </div> -->
                </td>
            </tr>
        </table>
        <div class="signature">
            Medical Director: {{ $report_concerned_person_name }}<br />
            NPI: {{ $report_npi }}
        </div>
        <!--<footer style="text-align: right;">
            ***Laboratory Director: {{ $report_concerned_person_name }} ** Report Printed On: {{ date('m/d/Y H:i') }}***
        </footer>-->
    </div>
</div>