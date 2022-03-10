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
    .resultTable th, .resultTable td {
        padding: 8px;
        border: 1px solid #e2e2e2;
        font-size: 15px;
        text-align: left;
    }
    .commentBlock {
        text-align: left;
    }
    .commentBlock p, .commentBlock li {
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
    .commentBlock .content ol, .commentBlock .content ul {
        margin-bottom: 5px;
    }
    .commentResult th, .commentResult td {
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
    @page { margin: 10px; }
    body { margin: 10px; font-family: Arial, Helvetica, sans-serif; }
</style>
<div class="reportwrap" >
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
                            Basedon Revisions to Rule 64D-3.029<br />
                            Florida AdministrativeCode<br />
                            Effective October 20, 2016<br />
                        </p>
                    </div>
                </td>
            </tr>
        </table>
        <br/>
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
                        <td><strong>Account#</strong> 1659461101</td>
                        <td><strong>Refer#</strong> 51209</td>
                        <td><strong>Passport#</strong> AAB115708</td>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="width: 50%;">
                    <div class="labAddress">
                        INTI FERNANDEZ MD<br>2100 NW 42 AVE<br>MIAMI, Florida 33126
                    </div>
                    <div class="labTel">
                        <strong>TEL:</strong> {{ $report_lab_phone }}
                        <br>
                        <strong>FAX:</strong> 305-869-1167
                    </div>
                    <div><strong>Doctor:</strong> INTI FERNANDEZ MD</div>
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
                        <div><strong>COLLECTED:</strong> {{ $report_specimen_collection_date }}</div>
                        <div><strong>REPORTED:</strong> {{ $report_result_date }}</div>
                    </div>
                </td>
            </tr>
            
        </table>
            <div class="descriptionTable">
                <table  cellspacing="0"class="resultTable" style="max-width: 100%; width: 100%; text-align: left ">
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
                                <div class="list">
                                <strong>Procedure:</strong> NAAT-RNA
                                </div>
                                <div class="list">
                                <strong>Specimen Type:</strong> {{ $report_test_type_method }}
                                </div>
                            </td>
                            <td>
                                <div class="list">
                                <strong>Testing Platform:</strong> Cepheid GeneXpert Xpress 
                                </div>
                                <div class="list">
                                    {{ $report_result }} For SARS-CoV-2
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
                                DR. B.N.Datta
                            </div>
                            Clinical Director
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <div class="labStamp" style="text-align: center;display: inline-block;">
                            Inti Fernando: M.D.<br>Miami International Airport<br>Ground Lave!<br>Miami. FL 33142 
                        </div>
                    </td>
                </tr>
            </table>      
    </div>
</div>