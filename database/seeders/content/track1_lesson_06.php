<?php

/**
 * Lesson 6 — Sensors (part 1).
 *
 * Content drawn from docs.pilot-gps.com 7.10:
 *   · Sensor types                     /sensor_types.html
 *   · How to add a sensor              /how_to_add_a_sensor_2.html
 *   · Configuring the fuel sensor      /configuring_the_fuel_sensor.html
 *   · How to work with a list of sensors /how_to_work_with_a_list_of_sensors.html
 */

return [
    'module_subtitle' => 'Sensors (part 1)',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Add an ignition sensor (two-position); verify via Points tab' => [
            'docs' => 'Sensors → Sensor types · How to add a sensor',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<div class="lede"><p>Sensors are the bridge between raw hardware signals and meaningful business intelligence. In PILOT, every telemetry metric — from engine ignition to fuel level — is handled by a configured sensor.</p></div>

<h2 id="sensor-function-vs-type">Function vs Operation Principle (Type)</h2>

<p>When adding a sensor, PILOT separates two concepts:</p>

<ul>
<li><strong>Sensor Function</strong> — what the sensor measures in real life (e.g. <em>Ignition sensor</em>, <em>Fuel sensor</em>, <em>Temperature sensor</em>, <em>SOS Button</em>, <em>CAN Mileage</em>).</li>
<li><strong>Operation Principle (Type)</strong> — how the mathematical engine interprets the data stream. Types include <em>Two-position</em>, <em>Discrete</em>, <em>Pulse</em>, <em>Multi-position</em>, and <em>Composite</em>.</li>
</ul>

<p>When you select a function, PILOT automatically selects the standard operation principle, but you can adjust it if your hardware wiring demands it.</p>

<h2 id="two-position-sensors">What is a Two-Position sensor?</h2>

<p>A <strong>Two-position sensor</strong> captures binary states: ON / OFF, OPEN / CLOSED, ACTIVE / INACTIVE. The engine determines state based on a defined threshold:</p>

<ul>
<li>If incoming value is between the minimum and maximum threshold (e.g. 1.0 to 1.0), the sensor is reported as <strong>ON (1)</strong>.</li>
<li>If outside that range (e.g. 0.0), it reports as <strong>OFF (0)</strong>.</li>
<li>For ignition, this state directly controls whether the vehicle is recorded as <em>Moving</em>, <em>Idling</em>, or <em>Parked</em>.</li>
</ul>

<h2 id="step-by-step-ignition">Step-by-step: Adding an Ignition Sensor</h2>

<ol>
<li>In the Online workspace, right-click your vehicle and select <strong>Sensors</strong> (or open the Object Card and switch to the <strong>Sensors</strong> tab).</li>
<li>Click the <strong>Add sensor</strong> button (plus icon).</li>
<li>In the <strong>Sensor function</strong> dropdown, choose <strong>Ignition sensor</strong>.</li>
<li>Notice that the <strong>Type</strong> field automatically populates with <strong>Two-position</strong>.</li>
<li>Enter a descriptive name: <code>Engine Ignition</code>.</li>
<li>In the <strong>Parameter</strong> field, select the hardware telemetry tag coming from the device (e.g. <code>acc</code>, <code>ignition</code>, or <code>din1</code>).</li>
<li>Set the <strong>Active state threshold</strong>: Min = <code>1</code>, Max = <code>1</code>.</li>
<li>Click <strong>Save</strong>.</li>
</ol>

<h2 id="verify-points">Verifying sensor readings via the Points tab</h2>

<p>Never assume a sensor works until you verify its raw telemetry stream:</p>

<ol>
<li>Open the vehicle’s sensor settings window and switch to the <strong>Points</strong> tab.</li>
<li>The Points tab shows a chronological log of raw telemetry packets received directly from the GPS device.</li>
<li>Locate the parameter column you mapped (e.g. <code>din1</code> or <code>acc</code>).</li>
<li>Verify that when the engine starts, the value flips from <code>0</code> to <code>1</code>, and when turned off, it returns to <code>0</code>.</li>
<li>If the value flips in reverse (shows 0 when running, 1 when off), change the sensor type to <strong>Inverse two-position</strong>.</li>
</ol>
HTML,
        ],

        // ─────────────────────────────────────────────────────────
        'Add a fuel level sensor (discrete) and fill main parameters' => [
            'docs' => 'Sensors → Sensor types → Configuring the fuel sensor',
            'estimated_minutes' => 20,
            'body' => <<<'HTML'
<div class="lede"><p>Fuel monitoring is one of the highest-value services telematics providers deliver. Configuring fuel sensors correctly requires understanding noise filtration, filling detection, and drain thresholds.</p></div>

<h2 id="discrete-sensors">What is a Discrete sensor?</h2>

<p>A <strong>Discrete sensor</strong> transmits numeric values across a continuous or stepped range (e.g. 0 to 4095 raw ADC counts, or 0 to 180 Ohm resistance). These raw values are converted into real liters using a calibration table or conversion formula.</p>

<h2 id="fuel-parameters">Critical fuel configuration parameters</h2>

<p>In PILOT, fuel algorithms rely on four core parameters to eliminate false alarms caused by fuel sloshing in tanks:</p>

<dl>
<dt><strong>Refuel threshold (Liters)</strong></dt>
<dd>The minimum volume jump required to count as a genuine refuel event (e.g. <code>10</code> or <code>15</code> liters). Jumps smaller than this threshold are treated as normal road sloshing and ignored.</dd>

<dt><strong>Drain threshold (Liters)</strong></dt>
<dd>The minimum sudden volume drop that triggers a fuel theft / drain alert (e.g. <code>10</code> liters).</dd>

<dt><strong>Maximum speed for filling detection (km/h)</strong></dt>
<dd>Vehicles cannot be refueled while driving at highway speed. If the vehicle is moving faster than this value (e.g. <code>5</code> km/h), volume spikes caused by driving up steep hills or sharp braking will <strong>never</strong> be logged as refuels or drains. A value of <code>0</code> disables this essential filter.</dd>

<dt><strong>Remove symmetric fillings / drains</strong></dt>
<dd>When checked, PILOT automatically detects and removes temporary symmetric spikes (e.g. fuel surges 20L uphill, then drops 20L downhill a minute later). This keeps operational consumption metrics clean.</dd>
</dl>

<h2 id="step-by-step-fuel">Step-by-step: Adding a Fuel Level Sensor</h2>

<ol>
<li>In the vehicle's sensor window, click <strong>Add sensor</strong>.</li>
<li>In the <strong>Sensor function</strong> dropdown, select <strong>Fuel sensor</strong>.</li>
<li>The <strong>Type</strong> automatically defaults to <strong>Discrete</strong>.</li>
<li>Enter the name: <code>Main Fuel Tank</code>.</li>
<li>Select the hardware input parameter (e.g. <code>fuel_level</code>, <code>lls1</code>, or <code>ain1</code>).</li>
<li>Set <strong>Refuel threshold</strong> to <code>15</code> liters.</li>
<li>Set <strong>Drain threshold</strong> to <code>10</code> liters.</li>
<li>Set <strong>Maximum speed for filling detection</strong> to <code>5</code> km/h.</li>
<li>Check the <strong>Remove symmetric fillings/drain</strong> option.</li>
<li>Ensure the <strong>Active</strong> checkbox is ticked so PILOT records all fuel movements.</li>
<li>Click <strong>Save</strong>.</li>
</ol>
HTML,
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 6 — knowledge check',
        'description' => 'Five questions on sensor functions, two-position thresholds, discrete sensors, and fuel filtration settings.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'Which sensor type captures binary states like engine ON/OFF or door OPEN/CLOSED?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Two-position sensors determine on/off states based on whether the incoming telemetry value falls within the specified min/max threshold range.',
                'options' => [
                    ['text' => 'Two-position sensor', 'correct' => true],
                    ['text' => 'Pulse integral sensor', 'correct' => false],
                    ['text' => 'Multi-position sensor', 'correct' => false],
                    ['text' => 'Text sensor', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A customer reports that a vehicle repeatedly logs false "Refuel" events while driving on bumpy gravel roads. Which setting should you check and adjust?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Setting "Maximum speed for filling detection" (e.g. 5 km/h) prevents fuel sloshing caused by vehicle motion from ever registering as a refuel or drain event.',
                'options' => [
                    ['text' => 'Set "Maximum speed for filling detection" to 5 km/h so level changes while moving are ignored', 'correct' => true],
                    ['text' => 'Increase the device reporting rate to 1 packet per second', 'correct' => false],
                    ['text' => 'Change the sensor type from Discrete to Pulse', 'correct' => false],
                    ['text' => 'Delete the fuel sensor and recreate it as an iButton sensor', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Where can an agent inspect the raw, unprocessed telemetry values arriving from a tracking device to verify sensor mapping?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'The Points tab in the sensor settings window displays the raw telemetry stream of incoming packets with their parameter names and raw values.',
                'options' => [
                    ['text' => 'On the Points tab inside the vehicle\'s sensor settings window', 'correct' => true],
                    ['text' => 'In browser cookies under Developer Tools', 'correct' => false],
                    ['text' => 'Only by calling the hardware manufacturer\'s support hotline', 'correct' => false],
                    ['text' => 'On the KML Settings tab in Account settings', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What does the "Remove symmetric fillings/drain" option accomplish in fuel sensor configuration?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'It eliminates false spikes where fuel surges and drops by roughly the same amount within a short window, such as when driving up and down inclines.',
                'options' => [
                    ['text' => 'It discards temporary fluctuations where fuel volume spikes and drops by nearly identical amounts', 'correct' => true],
                    ['text' => 'It automatically deducts 10% from all fuel invoices', 'correct' => false],
                    ['text' => 'It disables refuel detection on Sundays and public holidays', 'correct' => false],
                    ['text' => 'It ensures the fuel tank can never be filled above 50% capacity', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'If an ignition sensor shows "1" when the vehicle engine is turned off, and "0" when the engine is running, how should you correct it?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'When physical wiring or hardware logic inverts the voltage signal, changing the sensor type to "Inverse two-position" flips the state in PILOT.',
                'options' => [
                    ['text' => 'Change the sensor type to "Inverse two-position"', 'correct' => true],
                    ['text' => 'Replace the vehicle alternator', 'correct' => false],
                    ['text' => 'Change the vehicle status color to Grey', 'correct' => false],
                    ['text' => 'Reinstall the PILOT application in the browser', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Configure ignition and fuel level sensors',
        'lesson_title' => 'Add an ignition sensor (two-position); verify via Points tab',
        'brief' => 'Open sensor settings for your test vehicle in the training account. Add a '
            .'two-position Ignition Sensor and verify its mapping; then add a discrete Fuel Sensor '
            .'with refuel, drain, and motion-filter thresholds configured.',
        'submission_instructions' => '1. Right-click your test vehicle and select Sensors.\n'
            .'2. Add an Ignition sensor (Two-position), assign parameter (e.g. din1 or acc), thresholds 1/1.\n'
            .'3. Open the Points tab to verify the raw data parameter is visible.\n'
            .'4. Add a Fuel sensor (Discrete), set Refuel threshold to 15L, Drain to 10L, Max speed 5 km/h, and enable symmetric filtering.\n'
            .'5. Take a screenshot showing both configured sensors in the Sensors table.',
        'requires_screenshot' => true,
        'estimated_minutes' => 25,
        'required_evidence' => [
            ['key' => 'agent_id', 'label' => 'Vehicle ID (Object ID / IMEI)', 'hint' => 'The ID of the vehicle where sensors were added'],
            ['key' => 'ignition_sensor_name', 'label' => 'Ignition Sensor Name', 'hint' => 'Name given to the ignition sensor (e.g. Engine Ignition)'],
            ['key' => 'fuel_sensor_name', 'label' => 'Fuel Sensor Name', 'hint' => 'Name given to the fuel sensor (e.g. Main Fuel Tank)'],
        ],
    ],
];
