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
    'module_subtitle' => 'Final testing and consultation',

    'lessons' => [

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
        ],
    ],
];
