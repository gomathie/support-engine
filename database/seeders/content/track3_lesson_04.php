<?php

/**
 * Support skills — Module D: Industry standard practice.
 *
 * ITIL and KCS. These are published industry standards rather than PILOT
 * facts, so they can be taught accurately here without inventing anything —
 * incident / service request / problem, priority as impact against urgency,
 * and KCS's rule about titling an article in the customer's words are all
 * standard and stable.
 *
 * The last two topics are the exception, and both are marked `needs_input`.
 * They are data-protection scenarios, and the reasoning is teachable while the
 * **company's actual position is not knowable from here**: who is the
 * controller and who the processor, and what a support agent is authorised to
 * do when a data subject makes a request. Getting that wrong is not a training
 * gap, it is a legal exposure — so the lessons teach the shape of the answer
 * and stop where the policy has to begin.
 *
 * Markup is limited to what Filament's rich editor round-trips — see §3 of
 * AGENTS.md.
 */

return [
    'module_subtitle' => 'Industry standard practice',

    'lessons' => [

        // ─────────────────────────────────────────────────────────
        'Sort the queue: 12 tickets → Incident / Service request / Problem + priority, impact and urgency justified separately' => [
            'docs' => null,
            'estimated_minutes' => 45,
            'body' => <<<'HTML'
<p><strong>Three categories and two dimensions. Getting them right is what stops a queue being
worked in the order things arrived.</strong></p>

<h2 id="the-three">Incident, service request, problem</h2>

<table>
<thead>
<tr><th>Type</th><th>What it is</th><th>What success looks like</th></tr>
</thead>
<tbody>
<tr><td><strong>Incident</strong></td><td>Something that worked is not working.</td><td>Service restored — as fast as possible, even by a workaround.</td></tr>
<tr><td><strong>Service request</strong></td><td>Something standard the customer is entitled to ask for. Nothing is broken.</td><td>Fulfilled correctly, within the expected time.</td></tr>
<tr><td><strong>Problem</strong></td><td>The underlying cause behind one or more incidents.</td><td>The cause understood and removed, so the incidents stop.</td></tr>
</tbody>
</table>

<blockquote><p><strong>The distinction that carries real weight:</strong> an incident is closed when
the customer is working again, even if you do not know why it broke. That is not sloppiness — it is
the definition. The "why" is a <em>problem</em>, and it is a separate piece of work with a different
clock on it.</p></blockquote>

<p>Collapsing the two is the most common failure in this module. Holding an incident open until the
root cause is found leaves the customer waiting for something they did not ask for.</p>

<h2 id="priority">Priority is not a feeling</h2>

<p>Priority is derived, from two things that must be judged <strong>separately</strong>:</p>

<ul>
<li><strong>Impact</strong> — how much is affected. One vehicle, one depot, or the whole fleet.
    Impact is about size, and it does not care how upset anyone is.</li>
<li><strong>Urgency</strong> — how fast the damage grows. A cold-chain lorry losing temperature
    monitoring is urgent because the cargo is spoiling now. A report that will not export is not
    urgent, however annoying.</li>
</ul>

<blockquote><p><strong>Justify them separately, then combine.</strong> The whole point of two
dimensions is that they disagree. One vehicle with a spoiling load is low impact and high urgency.
A whole fleet's mileage report being slightly wrong is high impact and low urgency. Merging them
into one gut number throws away the information.</p></blockquote>

<h2 id="the-drill">The drill</h2>

<p>Twelve tickets. For each: the type, the impact with a reason, the urgency with a
<em>separate</em> reason, and the priority that follows. You are marked on the two reasons, not on
the priority.</p>

<h2 id="the-trap">The trap in the twelve</h2>

<p>Several are loud and low priority, and at least one is quiet and high. The customer's volume is
not an input to either dimension. Recognising the quiet, serious one is what the exercise is for.</p>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the type is right, especially incident against problem.</li>
<li><strong>Method</strong> — impact and urgency argued separately, from evidence.</li>
<li><strong>Verification</strong> — you say what would change the priority.</li>
<li><strong>Communication</strong> — a reason each, in one sentence each.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Classifying and prioritising',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'A customer is working again but nobody knows why it broke. What do you do with the incident?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Close it — service is restored, which is the definition of a resolved incident — and raise a problem for the cause. They are separate pieces of work with separate clocks.',
                        'options' => [
                            ['text' => 'Close the incident and raise a problem for the cause', 'correct' => true],
                            ['text' => 'Hold the incident open until the cause is known', 'correct' => false],
                            ['text' => 'Close it and record the cause as unknown, with no follow-up', 'correct' => false],
                            ['text' => 'Reclassify it as a service request', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'One vehicle carrying a spoiling load has lost temperature monitoring. How does that score?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Low impact — one vehicle — and high urgency, because the damage is growing now. The dimensions are meant to disagree; merging them loses the information.',
                        'options' => [
                            ['text' => 'Low impact, high urgency', 'correct' => true],
                            ['text' => 'High impact, high urgency', 'correct' => false],
                            ['text' => 'Low impact, low urgency', 'correct' => false],
                            ['text' => 'High impact, low urgency', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Find the problem: spot the recurring symptom in a month of tickets' => [
            'docs' => null,
            'estimated_minutes' => 45,
            'body' => <<<'HTML'
<p><strong>A month of tickets, and somewhere in them the same thing has happened eleven times to
eleven different people who each thought they were unlucky.</strong></p>

<h2 id="the-brief">The brief</h2>

<p>Read a month of closed tickets. Find a recurring symptom, and make the case that it is one
problem rather than a coincidence.</p>

<h2 id="why-it-is-hard">Why nobody notices these in the moment</h2>

<p>Each ticket was handled correctly, closed within target, and by a different person. Nothing went
wrong. The pattern exists only across the set, and nobody is looking at the set — which is why this
has to be a deliberate exercise rather than something expected to happen naturally.</p>

<h2 id="what-to-look-for">What actually reveals a pattern</h2>

<ul>
<li><strong>The same workaround appearing repeatedly.</strong> The strongest signal there is. A
workaround used eleven times is a fix nobody has made.</li>
<li><strong>Clustering in time.</strong> Around a release, a configuration change, a month end.</li>
<li><strong>Clustering by customer type</strong> — one partner, one tariff, one module, one device
    type.</li>
<li><strong>Different words, same fault.</strong> The hardest to spot. Five customers describing
one thing in five vocabularies, none of which match.</li>
</ul>

<blockquote><p><strong>"Different words, same fault" is where the value is.</strong> Anything that
shows up under a consistent name has probably already been noticed. What hides is the fault
customers have no shared word for.</p></blockquote>

<h2 id="the-case">Making the case</h2>

<p>A problem record is a claim, and it needs the same discipline as a diagnosis:</p>

<ol>
<li>The <strong>symptom</strong>, described once, in a way all the instances fit.</li>
<li>The <strong>instances</strong> — ticket references, dates, who was affected.</li>
<li>What they have in <strong>common</strong>, and — the part that gets skipped — what they do
    <strong>not</strong>, so somebody else can test your grouping.</li>
<li>Your <strong>hypothesis</strong> for the cause, labelled as a hypothesis.</li>
<li>What it is <strong>costing</strong>: how many contacts a month, and what each takes.</li>
</ol>

<blockquote><p><strong>Point 5 is what gets it fixed.</strong> "This keeps happening" competes with
everything else on somebody's list. "This is forty minutes a week, every week, indefinitely" is an
argument.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the instances really are the same symptom.</li>
<li><strong>Method</strong> — you say what does not fit the grouping as well as what does.</li>
<li><strong>Verification</strong> — you say what evidence would disprove your grouping.</li>
<li><strong>Communication</strong> — the cost is quantified.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Finding the problem behind the incidents',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which is the strongest signal that several incidents share one problem?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The same workaround appearing repeatedly. A workaround used eleven times is a fix nobody has made.',
                        'options' => [
                            ['text' => 'The same workaround appearing repeatedly', 'correct' => true],
                            ['text' => 'Several tickets being closed late', 'correct' => false],
                            ['text' => 'The same agent handling them all', 'correct' => false],
                            ['text' => 'Customers using the same words', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What turns "this keeps happening" into something that gets fixed?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Quantifying the cost. "Forty minutes a week, every week, indefinitely" is an argument; "this keeps happening" competes with everything else on somebody\'s list.',
                        'options' => [
                            ['text' => 'Quantifying what it costs per month', 'correct' => true],
                            ['text' => 'Raising it with more senior people', 'correct' => false],
                            ['text' => 'Attaching more example tickets', 'correct' => false],
                            ['text' => 'Naming a likely cause confidently', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        "Write a KCS article titled in the customer's words — then find it by searching as a customer would" => [
            'docs' => null,
            'estimated_minutes' => 40,
            'body' => <<<'HTML'
<p><strong>An article nobody can find is not knowledge. It is a document.</strong></p>

<h2 id="the-rule">The rule</h2>

<blockquote><p><strong>Title it in the customer's words, not yours.</strong> They search for what
they experienced, not for what it turned out to be.</p></blockquote>

<table>
<thead>
<tr><th>Findable</th><th>Unfindable</th></tr>
</thead>
<tbody>
<tr><td>"My vehicle hasn't moved on the map since Friday"</td><td>"Positional data stagnation following device sleep"</td></tr>
<tr><td>"I can't see the geofences menu"</td><td>"Geozone module entitlement verification"</td></tr>
<tr><td>"My mileage report is wrong"</td><td>"CAN Mileage recalibration and period recalculation"</td></tr>
</tbody>
</table>

<p>The right-hand column is more accurate, and useless. Accuracy is what the body is for. The title
has exactly one job: to be the thing somebody types when they do not yet know what is wrong.</p>

<h2 id="the-brief">The brief</h2>

<ol>
<li>Write the article: title in the customer's words, then the symptom, the environment it applies
    to, the resolution, and the verification.</li>
<li><strong>Then find it.</strong> Search the knowledge base as a customer would — their vocabulary,
    their spelling, their guess at what to call it.</li>
<li>If you cannot find it in the first few results, <strong>the article failed</strong>. Change the
    title, not the search.</li>
</ol>

<h2 id="the-body">What the body needs</h2>

<ul>
<li><strong>Symptom</strong> — how it presents, in their words again.</li>
<li><strong>Environment</strong> — when this applies, and when it does not. An article that does not
    say when it applies gets applied when it does not.</li>
<li><strong>Resolution</strong> — the steps, in order, with the names on the screen.</li>
<li><strong>Verification</strong> — how the reader knows it worked.</li>
</ul>

<blockquote><p><strong>Write it while you are still solving it, not afterwards.</strong> An hour
later you will have forgotten what confused you, and what confused you is the most valuable thing
you had. Articles written from memory are always shorter and always miss the step that mattered.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the resolution works, and the environment section is honest about
    when it does not apply.</li>
<li><strong>Method</strong> — you searched as a customer and reported the result truthfully,
    including a failure.</li>
<li><strong>Verification</strong> — the article tells the reader how to confirm it worked.</li>
<li><strong>Communication</strong> — a customer could follow it without you.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'Writing findable knowledge',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'Which title is better for a knowledge article?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The title\'s only job is to be what somebody types when they do not yet know what is wrong. Accuracy belongs in the body.',
                        'options' => [
                            ['text' => '"My vehicle hasn\'t moved on the map since Friday"', 'correct' => true],
                            ['text' => '"Positional data stagnation following device sleep"', 'correct' => false],
                            ['text' => '"GPS troubleshooting guide (v2)"', 'correct' => false],
                            ['text' => '"Object reporting anomaly — diagnostic procedure"', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'You search as a customer would and cannot find your own article. What do you change?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'The title. The search is the test, not the thing being tested — an article nobody can find is not knowledge.',
                        'options' => [
                            ['text' => 'The title', 'correct' => true],
                            ['text' => 'The search terms', 'correct' => false],
                            ['text' => 'The article\'s category', 'correct' => false],
                            ['text' => 'Nothing — customers will ask support anyway', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Physical clock drill: 6 faults → "what happens in the real world while this stays broken?"' => [
            'docs' => null,
            'estimated_minutes' => 30,
            'body' => <<<'HTML'
<p><strong>Every fault on your screen has a clock running somewhere outside the building. This drill
is about learning to hear it.</strong></p>

<h2 id="the-question">The question</h2>

<blockquote><p><strong>What happens in the real world while this stays broken?</strong></p></blockquote>

<p>Six faults. For each, answer that question — concretely, with the thing and the timescale — and
then say what priority follows.</p>

<h2 id="worked">What a concrete answer looks like</h2>

<table>
<thead>
<tr><th>Fault</th><th>Vague</th><th>Concrete</th></tr>
</thead>
<tbody>
<tr><td>Temperature sensor not reporting on one lorry</td><td>"The customer can't see temperatures."</td><td>"A refrigerated load is unmonitored in transit. If it goes out of range nobody is told, and the load is written off on arrival — hours, not days."</td></tr>
<tr><td>Mileage slightly wrong across a fleet</td><td>"Reports are inaccurate."</td><td>"Driver pay and customer invoices are being calculated from it. Wrong for a month is a month of disputes and a re-run of payroll."</td></tr>
<tr><td>Geofence alerts not firing at one depot</td><td>"Alerts aren't working."</td><td>"Nobody knows when vehicles arrive, so the yard is not staffed for them. Drivers wait, and the effect is today's shift."</td></tr>
</tbody>
</table>

<h2 id="what-it-changes">What it changes</h2>

<p>Two things, and they run in both directions:</p>

<ul>
<li><strong>It corrects the priority.</strong> Some quiet faults are extremely expensive, and some
loud ones cost nothing at all. Urgency is about how fast the damage grows, and this is the question
that measures that.</li>
<li><strong>It changes what you say to the customer.</strong> "I understand this is blocking your
loading bay this afternoon" is a different conversation from "I've raised a ticket."</li>
</ul>

<blockquote><p><strong>The drill cuts both ways, and that is the point.</strong> One of the six will
be a fault that sounds serious and, when you ask this question honestly, costs nobody anything
before Monday. Being able to say so — and to justify not treating it as urgent — is as much the
exercise as spotting the expensive one.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — the consequence you describe actually follows from the fault.</li>
<li><strong>Method</strong> — you name the thing affected and the timescale, not a category.</li>
<li><strong>Verification</strong> — you say what you would ask the customer to confirm it.</li>
<li><strong>Communication</strong> — one sentence you could say to them, in their terms.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'The clock outside the building',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What does asking "what happens in the real world while this stays broken?" actually establish?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Urgency — how fast the damage grows. It is the dimension that impact does not capture.',
                        'options' => [
                            ['text' => 'Urgency — how fast the damage grows', 'correct' => true],
                            ['text' => 'Impact — how much is affected', 'correct' => false],
                            ['text' => 'Which team owns the ticket', 'correct' => false],
                            ['text' => 'Whether it is an incident or a problem', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'The question shows a loud-sounding fault costs nobody anything before Monday. What do you do?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Say so, and justify not treating it as urgent. The drill cuts both ways — de-escalating on evidence is as much the exercise as escalating.',
                        'options' => [
                            ['text' => 'Say so, and justify not treating it as urgent', 'correct' => true],
                            ['text' => 'Treat it as urgent anyway, since the customer is upset', 'correct' => false],
                            ['text' => 'Escalate it so somebody else decides', 'correct' => false],
                            ['text' => 'Close it as no fault found', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Role-play: fleet manager wants a weekend location report on one named driver' => [
            'docs' => null,
            'estimated_minutes' => 40,
            'needs_input' => 'The reasoning is teachable; the company position is not knowable from '
                .'here. Two things must come from a person: (1) whether PILOT is the data '
                .'processor and the customer the controller, or some other arrangement; and (2) '
                .'what a support agent is authorised to do with a request like this — refuse, '
                .'fulfil, or refer, and to whom. Igor and whoever owns data protection to supply '
                .'both. This is legal exposure, not a training gap.',
            'body' => <<<'HTML'
<p><strong>The request is legitimate-sounding, comes from a paying customer, and is the one where
being helpful can be the wrong answer.</strong></p>

<h2 id="the-scenario">The scenario</h2>

<p>A fleet manager asks for a location report covering the weekend, for one named driver. Not the
fleet. One person, outside working hours.</p>

<h2 id="what-changes">What makes this different from every other report request</h2>

<p>Nothing technical. The platform can produce it. What changes is that the subject is a person, the
period is their own time, and the request is about them individually rather than about the operation.</p>

<blockquote><p><strong>Three things move together, and each one alone would be routine.</strong> One
named individual · outside working hours · without an operational reason offered. Any one is normal.
All three at once is a different kind of request, and noticing that combination is the skill.</p></blockquote>

<h2 id="the-questions">The questions to ask</h2>

<ol>
<li><strong>What is the operational purpose?</strong> There may be an excellent one — a vehicle
    reported stolen, an accident, a load that never arrived. Asking is not an accusation.</li>
<li><strong>Does it need to be this person, or is the vehicle enough?</strong> Very often the answer
    is the vehicle, and the request narrows itself.</li>
<li><strong>Does the driver know their vehicle is tracked outside working hours?</strong></li>
<li><strong>Is this something you are authorised to produce, or something to refer?</strong></li>
</ol>

<h2 id="how-to-handle-it">How to handle it on the call</h2>

<ul>
<li><strong>Do not refuse abruptly.</strong> The request may be entirely proper, and treating a
    customer as a suspect is its own failure.</li>
<li><strong>Do not simply comply because they asked.</strong> "The customer requested it" has never
    been a sufficient reason on its own.</li>
<li><strong>Do not improvise the policy.</strong> This is the one call where making a sensible-
    sounding judgement yourself is the wrong move.</li>
<li><strong>Slow it down.</strong> "Let me check what we can do here and come back to you" is a
    complete, professional answer, and it costs nothing.</li>
</ul>

<h2 id="not-written">Where this lesson stops</h2>

<blockquote><p><strong>What you are actually permitted to do is not written here.</strong> Whether
this company is the controller or the processor, and what a first-line agent may do with a request
like this, are policy — and inventing them would be worse than useless. The lesson teaches you to
recognise the request and hold it safely; the policy has to come from a person. See the note on
this lesson.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — you recognised the combination that makes this different.</li>
<li><strong>Method</strong> — you asked the purpose question before deciding anything.</li>
<li><strong>Verification</strong> — you checked rather than judged.</li>
<li><strong>Communication</strong> — the customer was not made to feel accused, and was not told yes.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'A request about one person',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'What makes this request different from an ordinary report request?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Three things together: one named individual, outside working hours, with no operational reason offered. Any one alone is routine.',
                        'options' => [
                            ['text' => 'One named individual, outside working hours, with no purpose offered', 'correct' => true],
                            ['text' => 'The report covers a weekend', 'correct' => false],
                            ['text' => 'The platform cannot produce it', 'correct' => false],
                            ['text' => 'It came from a fleet manager rather than an administrator', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the right move when you are unsure whether you may produce it?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Slow it down and check. "Let me check what we can do here and come back to you" is a complete, professional answer — and this is the one call where improvising a sensible-sounding policy is the wrong move.',
                        'options' => [
                            ['text' => 'Say you will check and come back to them', 'correct' => true],
                            ['text' => 'Produce it — they are the paying customer', 'correct' => false],
                            ['text' => 'Refuse, and explain data protection to them', 'correct' => false],
                            ['text' => 'Produce it with the driver\'s name removed', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],

        // ─────────────────────────────────────────────────────────
        'Role-play: a driver calls asking what data is held on him and demands deletion' => [
            'docs' => null,
            'estimated_minutes' => 40,
            'needs_input' => 'Same dependency as the previous lesson, and sharper. A data subject '
                .'request has statutory deadlines and a defined route, and both depend on whether '
                .'this company is the controller or the processor for the driver\'s data. The '
                .'route a first-line agent must follow — who it goes to, in what time, and what '
                .'the driver is told meanwhile — has to be supplied. Igor and whoever owns data '
                .'protection.',
            'body' => <<<'HTML'
<p><strong>Not a customer. A driver, employed by a customer, asking what you hold about him and
telling you to delete it.</strong></p>

<h2 id="the-scenario">The scenario</h2>

<p>He is not on any account you can look up. He did not choose to be tracked. He is, in all
likelihood, entitled to ask — and the person he is asking is you, because you are the number he
found.</p>

<h2 id="what-you-must-not-do">The three things you must not do</h2>

<table>
<thead>
<tr><th>Do not</th><th>Why</th></tr>
</thead>
<tbody>
<tr><td><strong>Dismiss him.</strong> "You need to talk to your employer" as the whole answer.</td><td>It may well be the right route, but delivered as a brush-off it is both unhelpful and a risk. There is a difference between routing someone and getting rid of them.</td></tr>
<tr><td><strong>Confirm or deny what is held.</strong></td><td>You do not know who he is. Confirming that a named individual is tracked, to an unverified caller, is itself a disclosure.</td></tr>
<tr><td><strong>Delete anything.</strong></td><td>Whatever the right answer is, it is not a first-line agent acting on a phone call. Deletion may also be something the company is obliged <em>not</em> to do.</td></tr>
</tbody>
</table>

<h2 id="what-you-do">What you do</h2>

<ol>
<li><strong>Take it seriously and say so.</strong> He is very likely expecting to be fobbed off, and
    not being fobbed off changes the call.</li>
<li><strong>Record it accurately</strong> — the date, the time, what he asked for in his own words.
    Requests of this kind have clocks on them, and the clock usually starts when it was received,
    not when it reached the right desk.</li>
<li><strong>Do not verify his identity yourself</strong> unless that is your defined role.</li>
<li><strong>Route it by the defined path, immediately</strong>, and tell him you have done so and
    what happens next.</li>
</ol>

<blockquote><p><strong>The most important thing you do on this call is write down when it
happened.</strong> If there is a deadline, it started at the moment he rang — not when somebody
qualified read the ticket.</p></blockquote>

<h2 id="the-tension">The tension worth naming</h2>

<p>Everything else in this course has trained you to be helpful and to answer. Here, being helpful
means <em>not</em> answering, and that will feel like poor service. It is not. Recognising the small
number of situations where your instinct is wrong is why this exercise is at the end of the course.</p>

<h2 id="not-written">Where this lesson stops</h2>

<blockquote><p><strong>The route is not written here.</strong> Who this goes to, in what time, and
what the driver is told in the meantime are policy with a legal deadline attached, and they depend
on whether this company is the controller or the processor for his data. Nothing in this system
records it, and guessing it would be worse than leaving it open. See the note on this lesson.</p></blockquote>

<h2 id="marking">How it is marked</h2>

<ul>
<li><strong>Correctness</strong> — you neither confirmed, denied, nor deleted anything.</li>
<li><strong>Method</strong> — you recorded the date, time and exact request before doing anything
    else.</li>
<li><strong>Verification</strong> — you routed it by the defined path rather than improvising one.</li>
<li><strong>Communication</strong> — he ended the call taken seriously and knowing what happens next.</li>
</ul>
HTML,
            'quiz' => [
                'title' => 'A request from someone who is not the customer',
                'passing_score' => 70,
                'max_attempts' => 3,
                'questions' => [
                    [
                        'prompt' => 'An unverified caller asks whether you hold tracking data on him. Why not simply answer?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Confirming that a named individual is tracked, to a caller you have not verified, is itself a disclosure.',
                        'options' => [
                            ['text' => 'Confirming it to an unverified caller is itself a disclosure', 'correct' => true],
                            ['text' => 'The data is held by the customer, not by us', 'correct' => false],
                            ['text' => 'It would breach the customer\'s contract', 'correct' => false],
                            ['text' => 'Only the account administrator may be told', 'correct' => false],
                        ],
                    ],
                    [
                        'prompt' => 'What is the single most important thing you do on this call?',
                        'type' => 'single_choice',
                        'points' => 1,
                        'explanation' => 'Record the date, the time, and exactly what he asked for. If there is a deadline, it started when he rang — not when somebody qualified read the ticket.',
                        'options' => [
                            ['text' => 'Record when it happened and exactly what was asked', 'correct' => true],
                            ['text' => 'Verify his identity', 'correct' => false],
                            ['text' => 'Explain his rights to him', 'correct' => false],
                            ['text' => 'Tell him to contact his employer', 'correct' => false],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'quiz' => [
        'title' => 'Module D — knowledge check',
        'description' => 'Industry standard practice. You need 70% to pass.',
        'passing_score' => 70,
        'max_attempts' => 3,
        'time_limit_minutes' => null,

        'questions' => [
            [
                'prompt' => 'When is an incident resolved?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'When service is restored — even by a workaround, and even if nobody knows the cause yet. The cause is a problem, which is separate work.',
                'options' => [
                    ['text' => 'When service is restored, even if the cause is unknown', 'correct' => true],
                    ['text' => 'When the root cause has been found and removed', 'correct' => false],
                    ['text' => 'When the customer confirms they are satisfied', 'correct' => false],
                    ['text' => 'When a permanent fix has been deployed', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'Why are impact and urgency judged separately?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Because they disagree, and the disagreement is the information. One vehicle with a spoiling load is low impact and high urgency; merging them into one gut number throws that away.',
                'options' => [
                    ['text' => 'Because they often disagree, and the disagreement is the information', 'correct' => true],
                    ['text' => 'Because different teams own each one', 'correct' => false],
                    ['text' => 'Because urgency is set by the customer and impact by us', 'correct' => false],
                    ['text' => 'Because the SLA is calculated from urgency alone', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'What is the only job of a knowledge article\'s title?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'To be the thing somebody types when they do not yet know what is wrong. Accuracy belongs in the body.',
                'options' => [
                    ['text' => 'To be what somebody types before they know what is wrong', 'correct' => true],
                    ['text' => 'To describe the cause precisely', 'correct' => false],
                    ['text' => 'To identify the affected module', 'correct' => false],
                    ['text' => 'To distinguish it from similar articles', 'correct' => false],
                ],
            ],
            [
                'prompt' => 'A driver you cannot identify asks what data is held on him. What must you not do?',
                'type' => 'single_choice',
                'points' => 1,
                'explanation' => 'Confirm, deny, or delete. Confirming to an unverified caller is a disclosure; deletion is not a first-line act on a phone call.',
                'options' => [
                    ['text' => 'Confirm what is held, deny it, or delete anything', 'correct' => true],
                    ['text' => 'Record the time of the call', 'correct' => false],
                    ['text' => 'Tell him you are taking it seriously', 'correct' => false],
                    ['text' => 'Route it onward by the defined path', 'correct' => false],
                ],
            ],
        ],
    ],

    'practical_task' => [
        'title' => 'Sort a month of tickets and make the case for one problem',
        'lesson_title' => 'Find the problem: spot the recurring symptom in a month of tickets',
        'brief' => 'Read a month of closed tickets, find a recurring symptom, and make the case that it is one problem rather than a coincidence.',
        'submission_instructions' => 'Submit the symptom described once in a way all the instances fit, the ticket references, what they have in common AND what does not fit your grouping, your hypothesis labelled as a hypothesis, and what it costs per month in contacts and time. The cost is the part that gets it fixed. Say what evidence would disprove your grouping.',
        'requires_screenshot' => false,
        'estimated_minutes' => 45,
        'required_evidence' => [
            ['key' => 'symptom', 'label' => 'The symptom, described once', 'hint' => 'Phrased so that every instance fits it'],
            ['key' => 'instances', 'label' => 'Ticket references and dates', 'hint' => 'The instances you are grouping'],
            ['key' => 'does_not_fit', 'label' => 'What does not fit the grouping', 'hint' => 'So somebody else can test it'],
            ['key' => 'monthly_cost', 'label' => 'Cost per month', 'hint' => 'Contacts a month, and what each takes'],
        ],
    ],
];
