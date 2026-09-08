<?php

/**
 * Lesson 7 — Sensors (part 2).
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Calibration tables              /how_to_set_up_the_calibration_table_of_the_sensor.html
 *   · How to apply formulas           /how_to_apply_formulas.html
 *   · Sensor templates                /sensor_templates.html
 *   · Copying sensor settings         /copying_sensor_settings.html
 */

return [
    'module_subtitle' => 'Sensors (part 2)',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Set up a 3–4 point calibration table for the fuel sensor' => [
            'docs' => 'Sensors → How to set up the calibration table of the sensor',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<p><strong>Vehicle fuel tanks are rarely perfect rectangular boxes. Due to curved bases, wheel-well indentations, and irregular contours, 1 centimeter of fuel depth near the bottom does not equal 1 centimeter near the top. Calibration tables translate raw sensor metrics into exact liters.</strong></p>

<h2 id="why-calibration">Why calibration is indispensable</h2>

<p>A capacitive fuel level probe or factory float arm outputs raw electrical signals — such as millivolts (mV), frequency (Hz), or resistance (Ohm). For example, a float might report 180 Ohm empty and 20 Ohm full. A <strong>Calibration Table</strong> maps these raw readings (X) to true physical volume (Y) through linear interpolation between measured points.</p>

<h2 id="calibration-steps">Setting up a 4-point calibration table</h2>

<ol>
<li>Open the vehicle’s sensor settings and select your <strong>Fuel sensor</strong>.</li>
<li>Scroll down to the <strong>Calibration table</strong> section.</li>
<li>Select <strong>Graph calibration table</strong>.</li>
<li>Add reference points recorded during the physical tank dip test:
  <ul>
    <li>Point 1 (Empty tank): X = <code>0</code> (or minimum raw value), Y = <code>0</code> Liters</li>
    <li>Point 2 (Quarter tank): X = <code>1000</code>, Y = <code>25</code> Liters</li>
    <li>Point 3 (Half tank): X = <code>2000</code>, Y = <code>50</code> Liters</li>
    <li>Point 4 (Full tank): X = <code>4000</code>, Y = <code>100</code> Liters</li>
  </ul>
</li>
<li>Click the <strong>Graph view</strong> icon to visually inspect the interpolation line. The curve should rise smoothly without negative dips or reversals.</li>
<li>Click <strong>Save</strong>.</li>
</ol>

<blockquote><p><strong>Support tip:</strong> If a customer complains their fuel chart looks erratic or shows negative values, inspect the calibration table. If points are entered out of numerical order (e.g. X=1000 after X=2500), the interpolation engine calculates erratic slope transitions.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Fuel Calibration Tables',
                'description' => 'Verify your understanding of calibration curves, raw telemetry mapping, and interpolation rules.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What is the purpose of a Calibration Table in PILOT sensor configuration?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A calibration table maps non-linear raw sensor readings (mV, Hz, or counts) to true physical volumes (liters) using linear interpolation between known dip points.',
                        'options' => [
                            ['text' => 'It translates raw electrical sensor units (mV, Hz, Ohm) into true physical liters', 'correct' => true],
                            ['text' => 'It sets the maximum speed limit of the truck', 'correct' => false],
                            ['text' => 'It pairs Bluetooth accessories to mobile phones', 'correct' => false],
                            ['text' => 'It calculates billing subscriptions for SIM cards', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Why must calibration reference points (X values) always be entered in ascending numerical order?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Entering points out of order causes erratic interpolation math resulting in negative values or spike artifacts.',
                        'options' => [
                            ['text' => 'It ensures smooth linear interpolation without erratic calculation spikes or negative dips', 'correct' => true],
                            ['text' => 'PILOT will reject the device IMEI otherwise', 'correct' => false],
                            ['text' => 'It prevents cellular data overage fees', 'correct' => false],
                            ['text' => 'It is required by vehicle warranty regulations', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Apply formula /1000 to the battery voltage sensor' => [
            'docs' => 'Sensors → How to apply formulas',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>Conversion formulas allow you to perform immediate mathematical transformations on raw telemetry data as soon as packets arrive from the tracking device — before calibration or business rules are evaluated.</strong></p>

<h2 id="how-formulas-work">How conversion formulas work in PILOT</h2>

<p>The <strong>Conversion formula</strong> field in the sensor settings accepts standard arithmetic expressions. The incoming raw value from the sensor parameter is automatically fed into the formula.</p>

<p>A formula must start with an operator (<code>+</code>, <code>-</code>, <code>*</code>, <code>/</code>) or an equals sign (<code>=</code>):</p>

<ul>
<li><code>*10</code> — multiplies the raw input by 10.</li>
<li><code>/1000</code> — divides the raw input by 1000.</li>
<li><code>/25.5+7</code> — divides by 25.5 and offsets by 7.</li>
<li><code>=12.5</code> — forces a fixed value.</li>
</ul>

<blockquote><p><strong>Formatting rule:</strong> Decimal numbers in conversion formulas <strong>must</strong> use a period (dot), never a comma. Write <code>25.4</code>, not <code>25,4</code>.</p></blockquote>

<h2 id="battery-voltage-example">Case study: Battery Voltage (mV to V)</h2>

<p>Many GPS trackers (such as Teltonika devices) report external vehicle battery voltage in millivolts via parameter <code>vsource</code> or <code>power</code>. For instance, a 12V automotive battery might report a raw value of <code>12650</code>.</p>

<p>To display this as human-readable volts (<code>12.65 V</code>):</p>

<ol>
<li>Add or edit a <strong>Battery charge sensor</strong> (Discrete type).</li>
<li>Select the hardware parameter (e.g. <code>vsource</code>).</li>
<li>In the <strong>Conversion formula</strong> field, type: <code>/1000</code></li>
<li>Click <strong>Save</strong>.</li>
<li>When a packet with <code>12650</code> arrives, PILOT calculates <code>12650 / 1000 = 12.65</code>. The object tooltip and history charts will show <strong>12.65 V</strong>.</li>
</ol>
HTML,
            'quiz' => [
                'title' => 'Quiz: Sensor Conversion Formulas',
                'description' => 'Test your knowledge of conversion formula syntax, mathematical operators, and decimal notation.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'If a GPS tracker sends external battery voltage as millivolts (e.g. 12650 for 12.65V), which conversion formula should you apply in PILOT?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Dividing millivolts by 1000 (`/1000`) converts the value into standard volts (12.65 V).',
                        'options' => [
                            ['text' => '/1000', 'correct' => true],
                            ['text' => '*1000', 'correct' => false],
                            ['text' => '+1000', 'correct' => false],
                            ['text' => '=1000', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'Which decimal separator is strictly required when entering fractional numbers into PILOT conversion formulas?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'PILOT formulas require period notation (`.`, e.g. `25.4`), as commas will result in formula parsing errors.',
                        'options' => [
                            ['text' => 'A period / dot (e.g. 25.4)', 'correct' => true],
                            ['text' => 'A comma (e.g. 25,4)', 'correct' => false],
                            ['text' => 'A colon (e.g. 25:4)', 'correct' => false],
                            ['text' => 'A semicolon (e.g. 25;4)', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Save sensor config as a template and apply it to another object' => [
            'docs' => 'Sensors → Sensor templates',
            'estimated_minutes' => 15,
            'body' => <<<'HTML'
<p><strong>When onboarding a fleet of 50 identical trucks equipped with identical Teltonika FMB120 trackers and fuel probes, entering ignition thresholds, formulas, and calibration points 50 times manually is unacceptable. Sensor templates solve this completely.</strong></p>

<h2 id="hardware-compatibility">The golden rule of sensor templates</h2>

<blockquote><p><strong>A sensor template can only be applied to objects with the exact same GPS device model.</strong></p></blockquote>

<p>A template built for a <em>Teltonika FMB120</em> relies on FMB120 parameter names (<code>din1</code>, <code>vsource</code>, <code>lls1</code>). Applying it to a <em>Ruptela</em> or <em>Navtelecom</em> device would bind sensors to non-existent parameters, breaking telemetry reporting.</p>

<h2 id="creating-local-template">Saving a sensor template</h2>

<ol>
<li>Complete all sensor configurations on your reference vehicle (Ignition, Fuel with calibration table, Battery with <code>/1000</code> formula).</li>
<li>On the <strong>Sensors</strong> tab, click the <strong>Save template</strong> button (icon showing a template box).</li>
<li>Enter a descriptive name: <code>Teltonika FMB120 - Standard Fleet</code>.</li>
<li>Add a description noting the hardware wiring and tank capacity.</li>
<li>Click <strong>Save as new</strong>.</li>
</ol>

<h2 id="applying-template-single">Applying to another vehicle</h2>

<ol>
<li>Open the target vehicle's <strong>Sensors</strong> tab.</li>
<li>In the <strong>Template</strong> dropdown list, select <em>Teltonika FMB120 - Standard Fleet</em>.</li>
<li>Click <strong>Use template</strong>.</li>
<li>PILOT displays a preview of all sensors that will be created.</li>
<li>Click <strong>Apply</strong>. All sensors, formulas, and calibration curves are copied in seconds.</li>
</ol>

<h2 id="batch-application">Batch application to multiple vehicles</h2>

<p>To apply a template across 20 vehicles at once: in the Online object list, hold <code>Ctrl</code> and select all target vehicles. Right-click the selection, choose <strong>Sensor templates</strong>, pick your saved template, and click <strong>Use template</strong>.</p>
HTML,
            'quiz' => [
                'title' => 'Quiz: Sensor Templates & Batch Application',
                'description' => 'Test your understanding of hardware model constraints and applying sensor templates in bulk.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What is the mandatory prerequisite before applying a saved sensor template to a vehicle?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'A sensor template relies on hardware-specific telemetry tags and can only be applied to objects using the exact same tracker model.',
                        'options' => [
                            ['text' => 'The target object must use the exact same GPS tracker hardware model as the template', 'correct' => true],
                            ['text' => 'The vehicle must be currently driving over 50 km/h', 'correct' => false],
                            ['text' => 'The user must be the Super Administrator of the entire platform', 'correct' => false],
                            ['text' => 'The vehicle must have zero existing odometer mileage', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'How can an administrator apply a sensor template to 20 identical vehicles simultaneously in PILOT?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Operators can multi-select vehicles in the object list, right-click, select Sensor templates, and apply the template in one step.',
                        'options' => [
                            ['text' => 'Select all 20 vehicles in the object list, right-click, and choose Sensor templates → Use template', 'correct' => true],
                            ['text' => 'Create 20 separate administrative contracts', 'correct' => false],
                            ['text' => 'Reinstall the PILOT mobile app', 'correct' => false],
                            ['text' => 'Manually retype all formulas and calibration tables 20 times', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 7 — knowledge check',
        'description' => 'Four questions on calibration curves, conversion formulas, and sensor template compatibility.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Why is a calibration table necessary for a fuel level sensor on a commercial vehicle?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Fuel tanks have irregular geometries where depth does not scale linearly with volume, and sensors output electrical units (mV, Ohm) rather than liters.',
                'options' => [
                    ['text' => 'It translates non-linear raw electrical values into true volume in liters based on tank geometry', 'correct' => true],
                    ['text' => 'It calculates the driver\'s monthly salary based on fuel consumed', 'correct' => false],
                    ['text' => 'It automatically orders diesel from the nearest petrol station', 'correct' => false],
                    ['text' => 'It is required by law to pass government motor vehicle inspections', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A GPS tracker reports vehicle battery voltage in millivolts (e.g. 12400). What should you enter in the Conversion formula field to display 12.40 V?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Formulas begin with an arithmetic operator. To convert millivolts to volts, divide by 1000 by writing "/1000".',
                'options' => [
                    ['text' => '/1000', 'correct' => true],
                    ['text' => '-12000', 'correct' => false],
                    ['text' => '*0,001', 'correct' => false],
                    ['text' => '=12V', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Can a sensor template created for a Teltonika FMB120 tracker be applied to a vehicle fitted with a Ruptela Pro4 device?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'docs.pilot-gps.com explicitly states that sensor templates can only be applied to objects with the same type of GPS device, because parameter naming schemes differ between manufacturers.',
                'options' => [
                    ['text' => 'No, sensor templates are only compatible with objects sharing the exact same GPS device model', 'correct' => true],
                    ['text' => 'Yes, all GPS devices use identical parameter names and protocols', 'correct' => false],
                    ['text' => 'Yes, but only if both vehicles have the same engine size', 'correct' => false],
                    ['text' => 'Only if approved in writing by the hardware vendor', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'When entering decimal numbers in a PILOT conversion formula, which character must be used as the decimal separator?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'PILOT formulas strictly require a period (dot) for decimal values (e.g. 25.4, 1.75). Using a comma causes a mathematical parsing error.',
                'options' => [
                    ['text' => 'A dot / period (e.g. 25.5)', 'correct' => true],
                    ['text' => 'A comma (e.g. 25,5)', 'correct' => false],
                    ['text' => 'A semicolon (e.g. 25;5)', 'correct' => false],
                    ['text' => 'An underscore (e.g. 25_5)', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Calibrate sensors and create sensor template',
        'lesson_title' => 'Set up a 3–4 point calibration table for the fuel sensor',
        'brief' => 'Add a 4-point calibration table to your fuel sensor, apply a /1000 conversion '
            .'formula to a battery voltage sensor, save the complete configuration as a template, '
            .'and apply the template to a second vehicle.',
        'submission_instructions' => '1. Open the Fuel sensor settings on your first vehicle.\n'
            .'2. Add a 4-point calibration table: (0=0L, 1000=25L, 2000=50L, 4000=100L) and Save.\n'
            .'3. Add or edit a Battery charge sensor and enter "/1000" in the Conversion formula field.\n'
            .'4. Click "Save template", name it "Training Fleet Template", and save as new.\n'
            .'5. Open a second test vehicle and apply the saved template.\n'
            .'6. Take a screenshot showing the applied template and calibration graph.',
        'requires_screenshot' => true,
        'estimated_minutes' => 25,
        'required_evidence' => [
            ['key' => 'agent_id', 'label' => 'Source Vehicle ID', 'hint' => 'Object ID where calibration was configured'],
            ['key' => 'target_agent_id', 'label' => 'Target Vehicle ID', 'hint' => 'Object ID where template was applied'],
            ['key' => 'template_name', 'label' => 'Template Name', 'hint' => 'Name given to the sensor template'],
        ],
    ],
];
