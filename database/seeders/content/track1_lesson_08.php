<?php

/**
 * Lesson 8 — History and reports.
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · How to view history              /how_to_view_history_1.html
 *   · Viewing history                  /viewing_history.html
 *   · How to generate a report         /how_to_generate_a_report_1.html
 *   · Report types                     /report_types.html
 *   · How to work with a report        /how_to_work_with_a_report.html
 */

return [
    'module_subtitle' => 'History and reports',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Build 24h movement history for an object; review with the player' => [
            'docs' => 'History → How to view history · Viewing history',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>While the Online workspace shows where an object is right now, the History section provides a forensic record of everywhere it has been — reconstructed point by point with speed profiles, parking events, and sensor graphs.</strong></p>

<h2 id="building-history">Building movement history</h2>

<p>To view an object's past movements:</p>

<ol>
<li>Click <strong>History</strong> in the main navigation on the top bar.</li>
<li>Select the target vehicle from the left sidebar tree.</li>
<li>Click the <strong>Calendar icon</strong> to set the time window. Choose <em>Last 24 hours</em> or select a custom date and time range.</li>
<li>Click the <strong>Search</strong> button.</li>
<li>PILOT queries stored telemetry and displays a list of discrete trips and parking intervals in the bottom panel.</li>
<li>Check the boxes next to the tracks you want to visualize. The vehicle's route line appears on the map.</li>
</ol>

<h2 id="history-workspace-elements">The five components of the History screen</h2>

<ol>
<li><strong>List of selected tracks</strong> — shows start time, end time, duration, distance, and max speed for each driving leg.</li>
<li><strong>Track Player</strong> — interactive playback toolbar at the bottom of the map. Lets you play, pause, jump along the timeline, and adjust playback speed from 1x to 64x to review driving behavior.</li>
<li><strong>Events table</strong> — lists discrete trigger events occurring during the period (speed limit breaches, ignition toggles, geofence entries).</li>
<li><strong>Route map</strong> — visual polyline showing the exact path taken, with colored segments indicating speed brackets and icons marking stop locations.</li>
<li><strong>Telemetry Chart</strong> — synchronized graphical plot of sensor metrics (speed, fuel volume, temperature, battery voltage) plotted over the timeline. Hovering your mouse over the chart syncs with the vehicle icon on the map.</li>
</ol>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Create a "Mileage and Stops" report for yesterday' => [
            'docs' => 'Reports → How to generate a report · Report types',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>Reports transform millions of raw coordinates into summarized operational tables that business owners, fleet managers, and auditors rely on for billing and compliance.</strong></p>

<h2 id="generating-report">Step-by-step: Generating a Mileage and Stops report</h2>

<ol>
<li>Click <strong>Reports</strong> on the main top navigation menu.</li>
<li>In the left sidebar, check the box next to your target vehicle (or select an entire group).</li>
<li>Click the <strong>Calendar</strong> icon and select <strong>Yesterday</strong> (or a specific date).</li>
<li>In the <strong>Report type</strong> dropdown, select <strong>Mileage and Stops</strong> (one of the most common standard templates).</li>
<li>Configure report parameters:
  <ul>
    <li><strong>Split:</strong> choose <em>Not split</em> (for a single overall summary) or <em>Split by days</em>.</li>
    <li><strong>Report grouping:</strong> group by <em>Dates</em> (to review day-by-day progression) or by <em>Monitored objects</em> (to compare fleet vehicles).</li>
  </ul>
</li>
<li>Click <strong>Build report</strong>.</li>
</ol>

<h2 id="interpreting-report">Reading the Mileage and Stops table</h2>

<p>The resulting table breaks down the vehicle's operational day:</p>

<ul>
<li><strong>Total mileage</strong> — total kilometers traversed according to GPS calculation and CAN odometer.</li>
<li><strong>Engine operating time</strong> — hours and minutes ignition was active.</li>
<li><strong>Movement time vs Idling time</strong> — ratio of productive travel to wasteful stationary idling.</li>
<li><strong>Stops table</strong> — address of every stop, start time, end time, and duration. Stops longer than 30 minutes are clearly flagged.</li>
</ul>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Save the report in PDF and Excel formats' => [
            'docs' => 'Reports → How to work with a report → Exporting',
            'estimated_minutes' => 10,
            'body' => <<<'HTML'
<p><strong>A report on screen is useful for a support call, but clients need exportable artifacts for accounting, driver payroll, and client invoicing. PILOT supports immediate multi-format export.</strong></p>

<h2 id="export-formats">Supported export formats</h2>

<p>Above the generated report table, an export toolbar provides several output options:</p>

<ul>
<li><strong>Excel (XLSX)</strong> — full tabular data formatted into spreadsheet worksheets, including summary metrics and trip logs, ready for pivot tables or financial formulas.</li>
<li><strong>PDF</strong> — cleanly paginated, branded document with official headers, summary cards, and clean typography suitable for printing or emailing to company executives.</li>
<li><strong>CSV</strong> — raw delimited plain text for ingesting into external ERP, SAP, or logistics databases.</li>
<li><strong>Print</strong> — opens the browser's native print preview dialog.</li>
</ul>

<h2 id="exporting-steps">How to export</h2>

<ol>
<li>Generate the report table so data is visible on screen.</li>
<li>Click the <strong>Export to Excel</strong> button to download the <code>.xlsx</code> workbook.</li>
<li>Click the <strong>Export to PDF</strong> button to generate and download the formatted PDF document.</li>
<li>Open both files locally to verify all columns, totals, and timestamps exported cleanly.</li>
</ol>

<blockquote><p><strong>Support tip:</strong> If an exported PDF cuts off columns on the right, advise the customer to adjust table columns before exporting, or switch orientation to Landscape in their account report settings.</p></blockquote>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 8 — knowledge check',
        'description' => 'Four questions on movement history, track player tools, report generation, and exports.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Which section of PILOT allows an agent to replay an object\'s movement over a past 24-hour period with speed and sensor graphs?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The History section provides point-by-point movement reconstruction, track playback, event logs, and synchronized telemetry graphs.',
                'options' => [
                    ['text' => 'The History section on the top navigation bar', 'correct' => true],
                    ['text' => 'The Geocoder panel in map tools', 'correct' => false],
                    ['text' => 'The KML Settings tab in Account settings', 'correct' => false],
                    ['text' => 'The driver identification window', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the purpose of the Track Player in the History view?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Track Player lets you play, pause, scrub through time, and adjust playback speed (1x to 64x) to review historical movements.',
                'options' => [
                    ['text' => 'It allows you to simulate and review past trips along the route timeline with variable speed', 'correct' => true],
                    ['text' => 'It plays audio recordings from inside the vehicle cabin', 'correct' => false],
                    ['text' => 'It streams live FM radio to the driver\'s smartphone', 'correct' => false],
                    ['text' => 'It calculates the estimated resale value of the vehicle', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which standard report template breaks down total kilometers, driving time, idling time, and individual parking locations?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The "Mileage and Stops" report is the standard operational template summarizing travel distance, engine hours, idle intervals, and stop addresses.',
                'options' => [
                    ['text' => 'Mileage and Stops', 'correct' => true],
                    ['text' => 'RFID Badge Audit', 'correct' => false],
                    ['text' => 'Password Reset History', 'correct' => false],
                    ['text' => 'SIM Card Traffic Summary', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Which file formats can a generated report be exported to directly from the PILOT interface?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'PILOT allows reports to be saved as Excel (XLSX), PDF, CSV files, or sent directly to print.',
                'options' => [
                    ['text' => 'Excel (XLSX), PDF, and CSV', 'correct' => true],
                    ['text' => 'Only raw TXT notepad files', 'correct' => false],
                    ['text' => 'AutoCAD DXF and MP3 audio files', 'correct' => false],
                    ['text' => 'Reports cannot be exported from PILOT', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Build movement history and generate mileage report',
        'lesson_title' => 'Create a "Mileage and Stops" report for yesterday',
        'brief' => 'Build a 24-hour history track for a vehicle using the player and sensor chart; '
            .'then generate a "Mileage and Stops" report for yesterday, review the total distance, '
            .'and export the file as PDF or Excel.',
        'submission_instructions' => '1. Go to History, select your vehicle, choose the last 24 hours, click Search, and inspect the track.\n'
            .'2. Navigate to Reports, select the vehicle, set the period to Yesterday, and select "Mileage and Stops".\n'
            .'3. Click Build report and check the resulting summary table.\n'
            .'4. Click the PDF or Excel export icon to download the report file.\n'
            .'5. Submit a screenshot showing the built report table with total mileage and stop records.',
        'requires_screenshot' => true,
        'estimated_minutes' => 20,
        'required_evidence' => [
            ['key' => 'agent_id', 'label' => 'Vehicle ID (Object ID)', 'hint' => 'The vehicle for which the report was generated'],
            ['key' => 'total_mileage', 'label' => 'Total Mileage (km)', 'hint' => 'The total distance reported in the summary row'],
        ],
    ],
];
