<?php

/**
 * Lesson 12 — Final testing and consultation.
 *
 * Course wrapping, documentation lookup methodology, and Q&A framing:
 *   · Preparing for the competency final exam
 *   · Effective documentation research on docs.pilot-gps.com
 *   · Instructor-led error calibration
 */

return [
    'lesson_subtitle' => 'Review and consultation',

    'topics' => [

        // ─────────────────────────────────────────────────────────
        'Attend error review / Q&A session' => [
            'docs' => 'Before you start → Roles and access rights · Glossary',
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>You have covered the core functional breadth of PILOT — from basic entity models and user rights to complex sensor calibration, report builders, and live security tokens.</strong></p>

<h2 id="qa-objectives">Objectives of the Error Review & Q&A Session</h2>

<p>Before sitting the formal Competency Final Exam, every trainee meets with their assigned instructor for an interactive calibration session:</p>

<ul>
<li><strong>Reviewing practical task feedback:</strong> Examine marks and rubric comments from your trainer on user creation, vehicle setup, sensor calibration, and mileage reports.</li>
<li><strong>Clarifying edge cases:</strong> Discuss unusual hardware quirks (such as CAN bus data inversion, sporadic GPS drift in urban canyons, or dual-SIM failover).</li>
<li><strong>Support call etiquette:</strong> Rehearse framing technical diagnostic steps clearly without overwhelming the customer with jargon.</li>
</ul>

<blockquote><p><strong>Remember:</strong> In PILOT support, <em>"I don't know the answer right now, but let me consult the documentation and get right back to you"</em> is always better than guessing and making confident assertions that prove false. Accuracy builds trust.</p></blockquote>
HTML,
            'quiz' => [
                'title' => 'Quiz: Support Review & Error Calibration',
                'description' => 'Test core principles of support call etiquette, technical accuracy, and error calibration.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'In PILOT technical support, what is the professional course of action when faced with an unfamiliar technical problem on a live call?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Accuracy and honesty build customer trust. In telematics support, guessing leads to configuration mistakes; consulting documentation or confirming with senior engineers guarantees correct resolution.',
                        'options' => [
                            ['text' => 'State clearly that you will consult the official documentation or escalate to 2nd-line and follow up with the confirmed solution', 'correct' => true],
                            ['text' => 'Make up an answer immediately so the customer believes you know everything', 'correct' => false],
                            ['text' => 'Blame the customer\'s internet service provider without checking', 'correct' => false],
                            ['text' => 'Hang up the telephone and ignore future calls from that number', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'During the error calibration and practical review, what role do trainer rubric marks play in competency certification?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Competency in PILOT requires verified evidence (agent IDs, accounts) assessed against structured rubrics by qualified trainers.',
                        'options' => [
                            ['text' => 'They provide objective verification against real PILOT account identifiers (objects, sensors, accounts) to ensure tasks meet quality standards', 'correct' => true],
                            ['text' => 'They are purely optional suggestions that have no bearing on course completion', 'correct' => false],
                            ['text' => 'They automatically award full marks regardless of what was submitted', 'correct' => false],
                            ['text' => 'They reset all previous quiz attempts to zero', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Resolve one non-standard case using documentation' => [
            'docs' => 'docs.pilot-gps.com search and navigation',
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>No telematics engineer memorizes every setting of 200 different tracking devices and 50 software modules. The ultimate skill of a 1st-line support agent is navigating the official documentation rapidly under call pressure.</strong></p>

<h2 id="docs-research-strategy">Three strategies for rapid documentation research</h2>

<ol>
<li><strong>Use the Table of Contents hierarchy:</strong>
  When investigating an unfamiliar tool, navigate <a href="https://docs.pilot-gps.com/contents.html" target="_blank">docs.pilot-gps.com/contents.html</a>. Understanding whether a tool sits under <em>User account</em> (workspaces, sensors, reports) or <em>Admin panel</em> (contracts, billing, module provisioning) cuts search time in half.
</li>
<li><strong>Check Supported Equipment specs:</strong>
  When a customer asks <em>"Does PILOT support the Teltonika FMB920 Bluetooth temperature sensor?"</em>, jump directly to the <strong>Equipment</strong> section. It documents supported baud rates, port numbers, and specific telemetry parameter codes for over 1,000 tracker models.
</li>
<li><strong>Consult Release Notes for recent changes:</strong>
  If a customer on version 7.10 mentions a feature described differently in a legacy tutorial, check <em>What's new in PILOT 7.10</em> and <em>Release notes</em>. Parameter locations and UI layouts evolve between major releases.
</li>
</ol>

<h2 id="final-exam-readiness">Next step: The Final Exam</h2>

<p>When your instructor concludes your review session, proceed to the <strong>Track 1 Final Exam</strong>. Passing the exam along with your verified practical task submissions awards your official PILOT 1st-Line Support Competency Certificate.</p>
HTML,
            'quiz' => [
                'title' => 'Quiz: Documentation Research Methodology',
                'description' => 'Verify strategies for rapid, accurate technical lookup on docs.pilot-gps.com.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Where in docs.pilot-gps.com should you look to find baud rates, server ports, and parameter names for a specific GPS tracker model?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The Equipment section is the reference catalog for tracker models, supported connection ports, sensor wiring, and parameter names.',
                        'options' => [
                            ['text' => 'In the Equipment section, which documents telemetry parameters, ports, and configuration guides for supported hardware', 'correct' => true],
                            ['text' => 'In the Billing & Invoicing chapter', 'correct' => false],
                            ['text' => 'In the Map Basemaps configuration page', 'correct' => false],
                            ['text' => 'In the User Password Reset tutorial', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'When a feature layout in PILOT 7.10 looks different from older training material, which documentation section explains the latest interface changes?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Release Notes and "What\'s new in PILOT 7.10" document UI updates, renamed settings, and newly introduced features.',
                        'options' => [
                            ['text' => 'The "What\'s new in PILOT 7.10" section and Release Notes', 'correct' => true],
                            ['text' => 'The Terms of Service agreement', 'correct' => false],
                            ['text' => 'The server hardware BIOS manual', 'correct' => false],
                            ['text' => 'The company marketing blog', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Lesson 12 — knowledge check',
        'description' => 'Four questions assessing documentation lookup, support protocol, and competency certification readiness.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'What is the fundamental difference between the User Account and Admin Panel sections in the PILOT documentation hierarchy?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'User Account covers end-user tools like maps, sensors, and reports; Admin Panel covers contract administration, module licensing, and system configuration.',
                'options' => [
                    ['text' => 'User Account covers workspaces, tracking, sensors, and reports; Admin Panel covers contracts, billing, and module activation', 'correct' => true],
                    ['text' => 'User Account is for drivers; Admin Panel is only for vehicle hardware manufacturers', 'correct' => false],
                    ['text' => 'User Account is offline; Admin Panel is online', 'correct' => false],
                    ['text' => 'User Account is only available in English; Admin Panel is multilingual', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'If a customer asks whether a specific OBD-II tracker parameter is supported, which documentation section provides the technical protocol breakdown?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Supported Equipment lists hardware models, supported protocols, server port mappings, and parameter decoding details.',
                'options' => [
                    ['text' => 'Supported Equipment', 'correct' => true],
                    ['text' => 'Account Privacy settings', 'correct' => false],
                    ['text' => 'Report Scheduler documentation', 'correct' => false],
                    ['text' => 'Basemap provider licensing terms', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'When an issue cannot be resolved through documentation and requires 2nd-line escalation, what technical details should be collected?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Escalating a telematics issue requires exact identifiers (contract ID, vehicle agent ID/IMEI), timestamp, and error reproduction steps.',
                'options' => [
                    ['text' => 'Contract ID, object agent ID / IMEI, timestamp of the event, and specific error message or reproduction steps', 'correct' => true],
                    ['text' => 'Only the caller\'s first name and company address', 'correct' => false],
                    ['text' => 'The customer\'s credit card number and banking details', 'correct' => false],
                    ['text' => 'A photo of the vehicle\'s front tires', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What completes a course under the PILOT competency training model?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Under the competency model, completing a course requires reading lessons AND passing every practical task marked by a trainer AND passing the exam.',
                'options' => [
                    ['text' => 'Lessons opened and read AND every practical task passed by a trainer AND the final exam passed', 'correct' => true],
                    ['text' => 'Ticking a self-completion checkbox at the end of each lesson page', 'correct' => false],
                    ['text' => 'Spending at least 40 hours logged into the hub without submitting tasks', 'correct' => false],
                    ['text' => 'Downloading the certificate PDF before starting any lesson', 'correct' => false],
                ],
            ],
        ],
    ],
];
