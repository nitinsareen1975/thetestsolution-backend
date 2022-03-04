<style>
    .reportwrap {
        max-width: 990px;
        width: 100%;
        margin: auto;
        display: block;
    }
    .reportwrap table td {
        font-size: 12px;
        color: #000;
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
        padding: 2px 4px;
        border: 1px solid #000;
        font-size: 12px;
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
        font-size: 12px;
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
    @page { margin: 10px; }
    body { margin: 10px; font-family: Arial, Helvetica, sans-serif; }
</style>
<div class="reportwrap" >
    <div class="reportwrap-print">
        <table style="max-width: 100%;width:100%;margin:5px 0px 5px;">
            <tr>
                <td style="width: 50%">
                    <div xs={12} style="text-align: left;">
                        <img src="{{ $report_logo }}" alt="Logo" style="width: 100%;max-width:150px;max-height:55px;" />
                    </div>
                </td>
                <td style="width: 50%">
                    <div style="float: right; text-align:right">
                        <h3>Florida Health</h3>
                        <p>
                            Basedon Revisions to Rule 64D-3.029<br />
                            Florida AdministrativeCode<br />
                            Effective October 20, 2016<br />
                        </p>
                    </div>
                </td>
            </tr>
        </table>
        <br><br>
        <table style="max-width:100%;width:100%;">
            <tr>
                <td>
                    <div style="text-align:left;">
                        <table style="max-width:100%;width: 100%; border-spacing: 5px; border-collapse:separate;">
                            <tr>
                                <td style="width: 60%; border: 1px solid #000; ">
                                    <table class="infoTable" style="max-width:100%; width: 100%;">
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Patient NAME</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7;">: {{ $report_firstname }} {{ $report_lastname }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Date of birth</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_dob }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Sex</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_gender }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Address</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_street }}, {{ $report_city }}, {{ $report_state }}, {{ $report_zip }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Phone number</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_phone }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Ethnicity</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_ethnicity }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Pregnancy status</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_pregnent }}</td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="border: 1px solid #000; ">
                                    <table class="infoTable" style="max-width: 100%; width: 100% ">
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Specimen Collection date</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_specimen_collection_date }}</td>
                                        </tr>

                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Date of report</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_result_date }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Type of specimen</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_specimen_type }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Specimen collection site</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_specimen_site }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Phone number</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_lab_phone }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">Licence Number</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_licence_number }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">FDI Number</td>
                                            <td style="padding: 2px 4px; border-bottom: 1px solid #f7f7f7; ">: {{ $report_loinc }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
        <div style="border: 1px solid #000; margin: 0px 5px 10px ">
            <table style="max-width: 100%; width: 100% " cellspacing="0">
                <tr>
                    <td style="padding: 2px 4px; text-align: center ">DEPARTMENT OF MOLECULAR BIOLOGY</td>
                    <td style="padding: 2px 4px; text-align: right "> IN/OUT SAMPLE :Outhouse Sample</td>
                </tr>
            </table>
        </div>
        <div style="margin: 5px 5px 0px ">
            <table  cellspacing="0"class="resultTable" style="max-width: 100%; width: 100%; text-align: left ">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Result</th>
                        <th span={5}>Unit</th>
                        <th>Bio. Ref. Range</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $report_test_type_name }}</td>
                        <td>{{ $report_result }}</td>
                        <td></td>
                        <td>{{ $report_result }}</td>
                        <td>{{ $report_test_type_method }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="commentBlock" style="border: 1px solid #000; margin: 0px 5px 5px; border-top: none; padding: 5px ">
            <p><strong>Comment:</strong></p>
            <p><strong>Florida Health Registration No.: ATHCC</strong></p>
            <p><strong>Sample type: Nasopharyngeal & Oropharyngeal Swab</strong></p>
            <table class="commentResult">
                <thead>
                    <tr>
                        <th>Result</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $report_result }} RNA specific to {{ $report_test_type_name }} Detected</td>
                        <td>{{ $report_result_value }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="content">
                <p>Note: The Ct value is inversely proportional to the amount of genetic material (RNA) in the starting sample and can differ with the type of kit, sample collection, transport conditions etc.</p>
                <p><strong>Methodology</strong></p>
                <p>Real Time Reverse Transcription Polymerase Chain Reaction (RT PCR) test for the detection of RNA form SARS CoV2 in human nasopharyngeal and oropharyngeal swab specimens.</p>
                <p><strong>Clinical significance</strong></p>
                <p>SARS CoV 2 is the causative agent for corona virus disease 2019 or COVID-19 in Humans. SARS CoV 2 is a Beta Corona Virus, one of the four genera of Corona Viruses. Coronaviruses are enveloped non-segmented positive sense RNA
                    viruses belonging to the family coronaviridae and the order Nidovirales and broadly distributed in humans and other mammals. The common signs of COVID-19 infection include respiratory symptoms, fever, cough, shortness of breath
                    and breathing difficulties. In more severe cases, infection can cause pneumonia, severe acute respiratory syndrome, kidney failure and even death. Early and correct identification of infection with SARS CoV 2 is important for effective
                    isolation, treatment and case management of COVID-19.</p>
                <p><strong>Target Selection</strong></p>
                <p>The target sequence is N and ORF 1ab gene of SARS CoV2 when using Meril Covid19 kit and E gene, N gene and RdRp gene when using Hi PCR coronavirus multiplex Probe PCR kit.</p>
                <p><strong>Limitations</strong></p>
                <ol>
                    <li>
                        This kit is a qualitative kit that does not provide a quantitative value for the detected pathogens in the specimen.
                    </li>
                    <li>Positive results indicate infection but the possibility of infection with other similar viruses cannot be ruled out.</li>
                    <li>Negative result does not rule out COVID-19 infection. It should be interpretated along with the history, clinical findings and other epidemiological factors.</li>
                    <li>A not detected result means that SARS-CoV_2 RNA was not present in the specimen above the limit of detection. However, improper sample collection, handling, storage and transportation may result in false negative result. The report represents only the specimen received in the laboratory.</li>
                    <li>Negative results do not rule out possibly of SARS-CoV-2 infection and should not be used as the sole basis for patient management decisions. Presence of inhibitors, mutations and insufficient organism RNA can influence the result.</li>
                    <li>Positive result does not distinguish between viable and non-viable virus.</li>
                    <li>Viral load may differ at the beginning and towards the end of infection in an individual, thus repeat testing done on different days may show different results.</li>
                    <li>Various ICMR approved kits may have differences in test sensitivity, specificity and cut off values for PCR cycles, thus may result in difference of results.</li>
                </ol>
                <p>Note: Test is performed using ICMR approved kit.</p>
                <p><strong>References:</strong></p>
                <ul class="list-unstyled">
                    <li>The Institut Pasteur website:<br />
                        https://www.pasteur.fr/en/medical-center/disease-sheets/covid-19-disease-novel- coronavirus#symptoms. Accessed March 2020.</li>
                    <li>Center for Disease Control (CDC) website: https://www.cdc.gov/urdo/downloads/SpecCollectionGuidelines.pdf. Accessed March 2020.</li>
                    <li>CDC Interim Guidelines for Collecting, Handling, and Testing Clinical Specimens from Patients Under Investigation (PUIs) for 2019 Novel Coronavirus. https://www.cdc.gov/coronavirus/2019-nCoV/guidelines-clinical-specimens.html.
                        Accessed May 2020.</li>
                    <li>World Health Organization (WHO). Laboratory testing for coronavirus disease 2019 (COVID-19) in suspected human cases: Interim guidance, 2 March 2020.</li>
                </ul>
            </div>
        </div>
        <h4 style="max-width: 100%; width: 100%; text-align:center ">*** End Of Report ***</h4>
    </div>
    <footer class="reportFooter">
        <Row>
            <Col>
            <div class="doctorDetails">
                DR. B.N.Datta<br />
                Consultant Pathologist
            </div>
            </Col>
        </Row>
        <table>
            <thead>
                <tr>
                    <th>Report Authentication QR Code</th>
                    <th>Sample Collected At</th>
                    <th>Sample Processed</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <img style="max-width: 100px;" src="{{ $report_qrcode }}" />
                    </td>
                    <td>{{ $report_lab_name }}<br />
                        {{ $report_lab_street }}, {{ $report_lab_city }}, {{ $report_lab_state }}<br />
                        {{ $report_lab_zip }}</td>
                    <td>
                        {{ $report_lab_name }}<br />
                        {{ $report_lab_street }}, {{ $report_lab_city }}, {{ $report_lab_state }}<br />
                        {{ $report_lab_zip }}
                    </td>
                </tr>
            </tbody>
        </table>
    </footer>
</div>