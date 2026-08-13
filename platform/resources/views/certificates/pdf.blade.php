{{--
    Certificate PDF, rendered by dompdf in a queued job.

    Written against dompdf's limits rather than a browser's: no flexbox, no grid,
    no CSS custom properties, no webfonts. Absolute positioning inside a fixed
    A4 landscape page, and the prototype's palette written out as literal hex.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #334155;
            background: #ffffff;
        }

        .sheet {
            position: relative;
            width: 297mm;
            height: 210mm;
        }

        /* Two nested rules in the brand blue and amber, standing in for the
           prototype's radial gradients — dompdf renders neither gradients nor
           box-shadow. */
        .frame-outer {
            position: absolute;
            top: 10mm; left: 10mm; right: 10mm; bottom: 10mm;
            border: 2px solid #0284c7;
        }
        .frame-inner {
            position: absolute;
            top: 13mm; left: 13mm; right: 13mm; bottom: 13mm;
            border: 1px solid #eab308;
        }

        .body { position: absolute; top: 26mm; left: 26mm; right: 26mm; text-align: center; }

        .eyebrow {
            font-size: 9pt;
            letter-spacing: 4pt;
            text-transform: uppercase;
            color: #0284c7;
            margin-bottom: 4mm;
        }

        .title {
            font-size: 30pt;
            font-weight: bold;
            color: #0284c7;
            letter-spacing: -0.5pt;
            margin: 0 0 3mm;
        }

        .rule { width: 40mm; height: 3px; background: #eab308; margin: 0 auto 8mm; }

        .awarded { font-size: 10pt; color: #64748b; margin-bottom: 4mm; }

        .recipient {
            font-size: 26pt;
            font-weight: bold;
            color: #334155;
            margin-bottom: 6mm;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5mm;
        }

        .for { font-size: 10pt; color: #64748b; margin-bottom: 3mm; }

        .course { font-size: 16pt; font-weight: bold; color: #334155; margin-bottom: 10mm; }

        /* dompdf has no flexbox; a table is the reliable way to get three even
           columns that line up. */
        .meta { width: 100%; border-collapse: collapse; }
        .meta td {
            width: 33.33%;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            padding: 0 4mm;
        }
        .meta .label {
            display: block;
            letter-spacing: 2pt;
            text-transform: uppercase;
            font-size: 7pt;
            color: #cbd5e1;
            margin-bottom: 1.5mm;
        }
        .meta .value { font-size: 10pt; color: #334155; font-weight: bold; }

        .footer {
            position: absolute;
            bottom: 18mm; left: 26mm; right: 26mm;
            text-align: center;
            font-size: 7pt;
            color: #cbd5e1;
            letter-spacing: 1pt;
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="frame-outer"></div>
    <div class="frame-inner"></div>

    <div class="body">
        <div class="eyebrow">PILOT Training Hub</div>

        <h1 class="title">Certificate of Completion</h1>
        <div class="rule"></div>

        <div class="awarded">This is to certify that</div>
        <div class="recipient">{{ $certificate->recipient_name }}</div>

        <div class="for">has successfully completed</div>
        <div class="course">{{ $certificate->course_title }}</div>

        <table class="meta">
            <tr>
                <td>
                    <span class="label">Completed</span>
                    <span class="value">{{ $certificate->completed_at->format('j F Y') }}</span>
                </td>
                <td>
                    <span class="label">Certificate no.</span>
                    <span class="value">{{ $certificate->certificate_number }}</span>
                </td>
                <td>
                    <span class="label">Score</span>
                    <span class="value">
                        {{ $certificate->score !== null ? round($certificate->score).'%' : '—' }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Verify this certificate at {{ $certificate->verificationUrl() }}
    </div>
</div>
</body>
</html>
