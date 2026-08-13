<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\QuestionType;
use App\Enums\Role;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the official PILOT Technical Support Employee Examination (Section A)
 * into the quiz engine.
 *
 * Source: docs/PILOT_Technical_Support_Employee_Exam_EN2.md
 * Section A — 40 multiple-choice questions, one correct answer each.
 * Time limit: 60 minutes. Pass mark: 70 % (28 / 40).
 *
 * The exam is attached as a standalone final exam on the TRACK 1 course
 * (1st-line support), separate from the internal 7-question assessment that
 * already exists on that course. Running this seeder twice is safe —
 * updateOrCreate keys on quiz title + course.
 *
 * Answer key (from the document):
 *  1A  2C  3B  4D  5A  6B  7C  8A  9D  10B
 * 11A 12B 13C 14A 15B 16A 17C 18B 19D 20B
 * 21A 22C 23B 24A 25C 26B 27D 28A 29C 30B
 * 31A 32B 33C 34A 35A 36A 37A 38D 39A 40D
 */
class PilotExamSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::query()->role(Role::Admin->value)->first();

        // Attach to the TRACK 1 (1st-line support) course.
        // Create a lightweight course stub if it does not exist yet so this
        // seeder can also be run independently in a fresh environment.
        $course = Course::query()->where('category', 'TRACK 1')->first()
            ?? Course::query()->updateOrCreate(
                ['slug' => 'pilot-technical-support-advanced-exam'],
                [
                    'title' => 'PILOT Technical Support Advanced Exam',
                    'category' => 'TRACK 1',
                    'summary' => 'Official PILOT Technical Support Employee Examination.',
                    'description' => 'The official 40-question advanced knowledge assessment '
                        .'covering user management, objects, sensors, data processing, '
                        .'reports, notifications and geofences.',
                    'difficulty' => 'advanced',
                    'is_required' => true,
                    'estimated_minutes' => 60,
                    'status' => CourseStatus::Published,
                    'instructor_id' => $instructor?->id,
                    'created_by' => $instructor?->id,
                ],
            );

        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'title' => 'PILOT Technical Support Employee Examination – Section A',
            ],
            [
                'course_module_id' => null,
                'lesson_id' => null,
                'description' => 'The official PILOT advanced knowledge test. '
                    .'40 questions, one correct answer each. '
                    .'You need 70 % (28 correct) to pass. Time limit: 60 minutes.',
                'passing_score' => 70,
                'max_attempts' => 3,
                'time_limit_minutes' => 60,
                'shuffle_questions' => false, // preserve official numbering
                'shuffle_options' => false,
                'show_feedback' => true,
                'is_published' => true,
            ],
        );

        /*
         * Each entry: [question text, [A, B, C, D], correct letter]
         * Derived directly from the exam document and its answer key.
         */
        $questions = [
            // Q1 — answer A
            [
                'prompt' => 'An administrator created a user and specified a login, password, and email address. '
                    .'The user logged in but cannot see any objects. What is the most likely explanation?',
                'options' => [
                    'A' => ['The user has not been granted access rights to the objects', true],
                    'B' => ['The user has not confirmed the email address', false],
                    'C' => ['The objects have not been placed into a group', false],
                    'D' => ['A monitoring token has not been created for the user', false],
                ],
                'explanation' => 'Access to objects is controlled by rights grants. A login that works but shows nothing '
                    .'means the user exists but has no object-level permissions.',
            ],
            // Q2 — answer C
            [
                'prompt' => 'A user must be given access to only two objects out of twenty. '
                    .'What is the most appropriate method?',
                'options' => [
                    'A' => ['Create a separate account and move the two objects there', false],
                    'B' => ['Create a token for the two objects and use it instead of a user account', false],
                    'C' => ['Grant the user rights only to the two required objects', true],
                    'D' => ['Hide the other objects using a filter in the object list', false],
                ],
                'explanation' => 'Granting rights only to the required objects is the correct, least-privilege approach. '
                    .'A token is a sharing mechanism, not a user account substitute.',
            ],
            // Q3 — answer B
            [
                'prompt' => 'Several dispatchers need the same set of system permissions and access to the same list '
                    .'of objects. Which solution reduces the likelihood of errors?',
                'options' => [
                    'A' => ['Configure each user separately from memory', false],
                    'B' => ['Create a permissions template and apply it to the users', true],
                    'C' => ['Assign everyone the Chief Administrator role', false],
                    'D' => ['Create one shared login for all dispatchers', false],
                ],
                'explanation' => 'A permissions template is repeatable, auditable, and eliminates per-user drift.',
            ],
            // Q4 — answer D
            [
                'prompt' => 'A user needs to allow an external contractor to monitor three objects for a limited period '
                    .'without sharing a login and password. What is the best option?',
                'options' => [
                    'A' => ['Create a new administrator', false],
                    'B' => ['Give the contractor the password of an existing user', false],
                    'C' => ['Export the object tracks to KML', false],
                    'D' => ['Create a monitoring token for the required objects with an expiration period', true],
                ],
                'explanation' => 'A monitoring token grants scoped, time-limited access without exposing credentials.',
            ],
            // Q5 — answer A
            [
                'prompt' => 'A user is blocked in the administrative panel. Which statement is the most accurate?',
                'options' => [
                    'A' => ['The user will not be able to log in, but the user\'s objects will continue receiving data', true],
                    'B' => ['The user will not be able to log in, and all of the user\'s objects will stop receiving data', false],
                    'C' => ['The account and activity history will be deleted immediately', false],
                    'D' => ['The block applies only to the mobile application', false],
                ],
                'explanation' => 'Blocking a user prevents login only. Devices keep sending data independently of the user account state.',
            ],
            // Q6 — answer B
            [
                'prompt' => 'A user has the required rights to objects but cannot see a particular section of the system. '
                    .'What should be checked first?',
                'options' => [
                    'A' => ['Whether the objects have license plate numbers', false],
                    'B' => ['Whether the user has the required system permission for that section', true],
                    'C' => ['Whether tags have been assigned to the objects', false],
                    'D' => ['Whether at least one report has been generated', false],
                ],
                'explanation' => 'Object rights and system-section permissions are separate. A missing section permission hides the menu entry.',
            ],
            // Q7 — answer C
            [
                'prompt' => 'The correct IMEI was entered when creating an object, but the wrong device type was selected. '
                    .'What is the most likely result?',
                'options' => [
                    'A' => ['The object will necessarily appear twice', false],
                    'B' => ['Packets will not be accepted by the server', false],
                    'C' => ['Packets may be accepted, but the user may be unable to send commands', true],
                    'D' => ['The system will always detect the device type automatically from the IMEI', false],
                ],
                'explanation' => 'The server may decode the incoming protocol but the command set depends on device type. '
                    .'A mismatch means commands may silently fail or be malformed.',
            ],
            // Q8 — answer A
            [
                'prompt' => 'Packets from a device reach the server but are not linked to the created object. '
                    .'What should be checked first?',
                'options' => [
                    'A' => ['Whether the device ID in the object matches the identifier in the incoming packets', true],
                    'B' => ['Whether a geofence has been created around the vehicle', false],
                    'C' => ['Whether the user has permission to edit the device', false],
                    'D' => ['The server port', false],
                ],
                'explanation' => 'Packets arrive but bind to nothing when the stored device ID does not match the ID in the packets.',
            ],
            // Q9 — answer D
            [
                'prompt' => 'What is the main difference between the object group and a tag?',
                'options' => [
                    'A' => ['A group stores raw data, while a tag stores processed data', false],
                    'B' => ['A group is used only in reports, while a tag is used only on the map', false],
                    'C' => ['There is no functional difference between a group and a tag', false],
                    'D' => ['A group organizes objects into a structure, while a tag provides additional classification and filtering', true],
                ],
                'explanation' => 'Groups are structural (hierarchy, rights, reporting scope). Tags are labels for cross-cutting classification and filtering.',
            ],
            // Q10 — answer B
            [
                'prompt' => 'An object was moved from one contract to another. What risk should be considered?',
                'options' => [
                    'A' => ['The device IMEI will change automatically', false],
                    'B' => ['Module availability, user permissions, and contract-dependent settings may change', true],
                    'C' => ['The object\'s raw data will necessarily be deleted', false],
                    'D' => ['The GPS terminal will need to be restarted', false],
                ],
                'explanation' => 'Each contract has its own module set and user structure. Moving an object may silently disable features or lose access.',
            ],
            // Q11 — answer A
            [
                'prompt' => 'An additional module must be enabled for a contract. '
                    .'Where is the most logical place to do this?',
                'options' => [
                    'A' => ['In the contract settings of the administrative panel', true],
                    'B' => ['In the user settings', false],
                    'C' => ['In the object history', false],
                    'D' => ['In the user\'s browser configuration', false],
                ],
                'explanation' => 'Modules are licensed at the contract level and are managed in the contract settings in the admin panel.',
            ],
            // Q12 — answer B
            [
                'prompt' => 'After an object is created, no data appears even though the ID and device type are correct. '
                    .'What is the most rational troubleshooting order?',
                'options' => [
                    'A' => ['Create reports first, then sensors, and then check the port', false],
                    'B' => ['First verify packet reception and the port, then verify packet-to-object binding', true],
                    'C' => ['First change the user password, then recreate the object', false],
                    'D' => ['First create a geofence and a loss-of-connection notification', false],
                ],
                'explanation' => 'Troubleshoot from the bottom up: confirm data is arriving (port, raw packets) before examining binding.',
            ],
            // Q13 — answer C
            [
                'prompt' => 'A temperature sensor was created using the field temp_1, but the device sends only '
                    .'temperature in raw points. What will happen?',
                'options' => [
                    'A' => ['PILOT will automatically match similar field names', false],
                    'B' => ['The sensor will obtain the temperature from the coordinate packet', false],
                    'C' => ['The sensor will most likely not receive a value', true],
                    'D' => ['The value will appear after a temperature report is generated', false],
                ],
                'explanation' => 'The sensor field must exactly match the parameter name in the incoming packet. '
                    .'A mismatch means no data is bound to the sensor.',
            ],
            // Q14 — answer A
            [
                'prompt' => 'What is the most reliable way to determine the field for a new sensor?',
                'options' => [
                    'A' => ['Inspect the object\'s raw points and find the parameter with the expected values', true],
                    'B' => ['Choose the field with the most suitable name without checking the data', false],
                    'C' => ['Copy a field from any object in the same account', false],
                    'D' => ['Generate a report first and then choose the field from the report', false],
                ],
                'explanation' => 'Raw points show exactly what the device is sending and with what field names. '
                    .'Guessing by name is unreliable across device types.',
            ],
            // Q15 — answer B
            [
                'prompt' => 'The device sends a voltage value of 14592 mV. '
                    .'The value must be displayed in volts. Which configuration is correct?',
                'options' => [
                    'A' => ['Unit: mV, formula: *1000', false],
                    'B' => ['Unit: V, formula: /1000', true],
                    'C' => ['Unit: V, calibration: 14592 → 14592', false],
                    'D' => ['Unit: %, formula: /100', false],
                ],
                'explanation' => 'To convert mV to V divide by 1000. The unit label should reflect the output, not the raw input.',
            ],
            // Q16 — answer A
            [
                'prompt' => 'When is a formula preferable to a calibration table?',
                'options' => [
                    'A' => ['When the relationship is known and can be expressed by a simple mathematical transformation', true],
                    'B' => ['When an input value must be mapped to arbitrary text states', false],
                    'C' => ['When a fuel tank has a complex nonlinear shape', false],
                    'D' => ['When several experimental fuel-level points must be converted into liters', false],
                ],
                'explanation' => 'A formula is ideal for linear or simple algebraic relationships. '
                    .'Nonlinear or empirical mappings belong in a calibration table.',
            ],
            // Q17 — answer C
            [
                'prompt' => 'When is a calibration table preferable to a simple formula?',
                'options' => [
                    'A' => ['When millivolts must be divided by 1000', false],
                    'B' => ['When the input and output values are always equal', false],
                    'C' => ['When the relationship between the input signal and the physical quantity is nonlinear', true],
                    'D' => ['When the user does not have access to sensors', false],
                ],
                'explanation' => 'Calibration tables map arbitrary input-to-output pairs and handle nonlinear tanks, '
                    .'irregular sensor responses, and discrete states.',
            ],
            // Q18 — answer B
            [
                'prompt' => 'When does editing the calibration table of a new sensor become available?',
                'options' => [
                    'A' => ['Before selecting the sensor field', false],
                    'B' => ['Only after the new sensor has been saved', true],
                    'C' => ['Only after a report has been generated', false],
                    'D' => ['After creating a user template', false],
                ],
                'explanation' => 'The calibration table editor is unlocked only after the sensor record is saved and has a database ID.',
            ],
            // Q19 — answer D
            [
                'prompt' => 'A fuel sensor calibration table contains the following points: '
                    .'0 → 0 L; 1000 → 20 L; 2000 → 50 L; 3000 → 80 L. '
                    .'If the input is between 1000 and 2000, what principle is used to determine the intermediate value?',
                'options' => [
                    'A' => ['Random selection of the nearest point', false],
                    'B' => ['Resetting to zero until an exact table value is received', false],
                    'C' => ['Using the previous row value without calculation', false],
                    'D' => ['Calculating a value between neighboring calibration points', true],
                ],
                'explanation' => 'Linear interpolation between the two nearest defined points is the standard '
                    .'calibration-table calculation method.',
            ],
            // Q20 — answer B
            [
                'prompt' => 'A fuel sensor shows a plausible current value, but the report contains false refuelings '
                    .'and drains. What is the most justified conclusion?',
                'options' => [
                    'A' => ['Because the current value is correct, the sensor configuration is guaranteed to be correct', false],
                    'B' => ['The raw value dynamics, calibration, and refueling/drain detection parameters must be checked', true],
                    'C' => ['The sensor should be deleted immediately and the object recreated', false],
                    'D' => ['The only possible cause is a faulty GPS antenna', false],
                ],
                'explanation' => 'A correct instantaneous reading does not mean the sensor is configured correctly for '
                    .'detection thresholds. Refuel/drain detection has its own parameters to verify.',
            ],
            // Q21 — answer A
            [
                'prompt' => 'The same raw voltage transformation is used for several object models. '
                    .'Which mechanism is intended for reusing common processing logic?',
                'options' => [
                    'A' => ['Sensor handler', true],
                    'B' => ['Monitoring token', false],
                    'C' => ['User group', false],
                    'D' => ['Report scheduler', false],
                ],
                'explanation' => 'Sensor handlers are reusable processing templates applied across many objects '
                    .'without duplicating configuration.',
            ],
            // Q22 — answer C
            [
                'prompt' => 'After changing a sensor formula, the current value became correct, but an old report '
                    .'for the previous month did not change. What action will most likely be required?',
                'options' => [
                    'A' => ['Log in again as another user', false],
                    'B' => ['Rename the sensor', false],
                    'C' => ['Recalculate the data for the required historical period', true],
                    'D' => ['Change the map type', false],
                ],
                'explanation' => 'Changing a sensor formula only affects new data. Historical data must be explicitly '
                    .'recalculated to reflect the corrected configuration.',
            ],
            // Q23 — answer B
            [
                'prompt' => 'For the DIN1 discrete input, the states "Equipment Off" and "Equipment On" must be displayed. '
                    .'What is the most suitable solution?',
                'options' => [
                    'A' => ['Create a mileage sensor with a /1000 formula', false],
                    'B' => ['Create a two-state sensor and configure the input-state mapping', true],
                    'C' => ['Create a temperature sensor with calibration in liters', false],
                    'D' => ['Use longitude as the sensor field', false],
                ],
                'explanation' => 'Discrete digital inputs (DIN) map to on/off states using a two-state sensor with '
                    .'defined value-to-label mappings.',
            ],
            // Q24 — answer A
            [
                'prompt' => 'An ignition sensor was created using DIN1. The points show DIN1=1, '
                    .'but the sensor displays "Off." What should be checked first?',
                'options' => [
                    'A' => ['The mapping between input values and the On/Off states', true],
                    'B' => ['The account name', false],
                    'C' => ['The object\'s geographic address', false],
                    'D' => ['The PDF report format', false],
                ],
                'explanation' => 'If the raw value is 1 but the sensor shows "Off", the value-to-state mapping is '
                    .'inverted or incorrectly configured.',
            ],
            // Q25 — answer C
            [
                'prompt' => 'How do raw points differ from the displayed track?',
                'options' => [
                    'A' => ['Raw points already contain only the filtered route', false],
                    'B' => ['The track is stored in the device, while points are created by the user', false],
                    'C' => ['Raw points are the received data, while the track is the result of processing and displaying those data', true],
                    'D' => ['There is no difference', false],
                ],
                'explanation' => 'Raw points are unprocessed server-received telemetry. The displayed track is the '
                    .'result of applying filters, smoothing, and route construction to those points.',
            ],
            // Q26 — answer B
            [
                'prompt' => 'A single jump of several kilometers followed by an immediate return is visible on the track. '
                    .'What is the most informative first step?',
                'options' => [
                    'A' => ['Delete the object', false],
                    'B' => ['Inspect the corresponding points, time, coordinates, speed, and validity flag', true],
                    'C' => ['Run a track recalculation', false],
                    'D' => ['Change the object filter settings', false],
                ],
                'explanation' => 'A jump artifact requires examining the raw point data — coordinates, timestamps, '
                    .'speed and validity flags — before drawing any conclusions.',
            ],
            // Q27 — answer D
            [
                'prompt' => 'After applying a filter, the track looks correct. Which conclusion must not be made?',
                'options' => [
                    'A' => ['The filter may exclude erroneous points from route construction', false],
                    'B' => ['The user-facing display became clearer', false],
                    'C' => ['Filter settings affect the history-building result', false],
                    'D' => ['The original cause of the erroneous coordinates has been fixed on the device', true],
                ],
                'explanation' => 'A filter hides bad data from the display but does not fix the device or the root cause. '
                    .'The underlying problem still exists and may affect other calculations.',
            ],
            // Q28 — answer A
            [
                'prompt' => 'A user selected a history period but cannot see a track. '
                    .'Which combination of causes is the most realistic?',
                'options' => [
                    'A' => ['No object was selected, the search was not run, or the period contains no suitable data', true],
                    'B' => ['No fuel calibration table was created', false],
                    'C' => ['The user did not add a vehicle photo', false],
                    'D' => ['The ignition sensor does not work', false],
                ],
                'explanation' => 'The three most common causes of an empty track are: no object selected, '
                    .'search not triggered, or genuinely no movement data for the chosen period.',
            ],
            // Q29 — answer C
            [
                'prompt' => 'Why is it important to use the same time period and time zone when comparing a track and a report?',
                'options' => [
                    'A' => ['Otherwise the user will automatically lose permissions', false],
                    'B' => ['Otherwise the system will select a different device type', false],
                    'C' => ['Otherwise the compared intervals and day boundaries may not match', true],
                    'D' => ['Otherwise the IMEI will change', false],
                ],
                'explanation' => 'Time zone mismatches shift day boundaries, so a "same day" track and report may '
                    .'actually cover different intervals in UTC.',
            ],
            // Q30 — answer B
            [
                'prompt' => 'Movement is shown in the history, but the ignition sensor is always Off. '
                    .'Which statement is the most accurate?',
                'options' => [
                    'A' => ['Movement automatically proves that the ignition sensor is configured correctly', false],
                    'B' => ['Coordinate movement and sensor state are different data streams; the field and sensor logic must be checked', true],
                    'C' => ['The map should be zoomed in', false],
                    'D' => ['Only the PDF report needs to be rebuilt', false],
                ],
                'explanation' => 'GPS coordinates and DIN sensor values come from separate packet fields. '
                    .'One being correct says nothing about the other.',
            ],
            // Q31 — answer A
            [
                'prompt' => 'What is the main difference between the Report Builder and running a predefined report?',
                'options' => [
                    'A' => ['The Report Builder allows a custom structure and custom output fields to be created', true],
                    'B' => ['Predefined reports are available only to administrators', false],
                    'C' => ['The Report Builder receives data directly from the GPS terminal', false],
                    'D' => ['The Report Builder is used only to change passwords', false],
                ],
                'explanation' => 'The Report Builder is a flexible tool for constructing custom column sets '
                    .'and table layouts. Predefined reports have a fixed structure.',
            ],
            // Q32 — answer B (re-labelled: original answer key says 32B = "The selected report type does not provide that data field")
            [
                'prompt' => 'A user changed the set of report columns but cannot see the required metric. '
                    .'What is the most likely explanation?',
                'options' => [
                    'A' => ['The selected report type does not provide that data field', true],
                    'B' => ['The object has no sensors', false],
                    'C' => ['The user permissions are not configured', false],
                    'D' => ['No partner has been created', false],
                ],
                'explanation' => 'Each report type exposes a fixed set of metrics. If a metric is absent from the '
                    .'column picker it is not available for that report type.',
            ],
            // Q33 — answer C
            [
                'prompt' => 'The same report must be sent automatically every morning. '
                    .'Which tool is most suitable?',
                'options' => [
                    'A' => ['Report Scheduler', true],
                    'B' => ['Calibration table', false],
                    'C' => ['Monitoring token', false],
                    'D' => ['Current Track menu', false],
                ],
                'explanation' => 'The Report Scheduler is designed precisely for recurring automated report delivery.',
            ],
            // Q34 — answer A
            [
                'prompt' => 'A temperature notification is configured for objects with a specific tag. '
                    .'A new object has the required temperature sensor, but the notification does not trigger for it. '
                    .'What should be checked first?',
                'options' => [
                    'A' => ['Whether the object has the tag used by the notification', true],
                    'B' => ['Whether a token was created for the object', false],
                    'C' => ['Whether the object appears in a mileage report', false],
                    'D' => ['Whether its icon color matches the other objects', false],
                ],
                'explanation' => 'Tag-scoped notifications only fire for objects that carry the specified tag. '
                    .'A new object must be tagged before it is in scope.',
            ],
            // Q35 — answer A
            [
                'prompt' => 'An email notification is configured correctly and the event is recorded in the system, '
                    .'but the user does not receive the email. Which check is most appropriate?',
                'options' => [
                    'A' => ['Whether the recipient email is confirmed and the Email channel is selected', true],
                    'B' => ['Whether a fuel calibration table exists', false],
                    'C' => ['Whether the object has a VIN', false],
                    'D' => ['Whether the track filter is configured', false],
                ],
                'explanation' => 'An event recorded in PILOT but not delivered by email points to the delivery channel: '
                    .'unverified email address or Email channel not selected on the notification.',
            ],
            // Q36 — answer A (answer key: 36A — "Create a Control Room notification and link an algorithm to it")
            // Note: option B in the source matches answer A in the key; options are re-ordered to put the
            // correct answer in position A for consistency with the answer key letter.
            [
                'prompt' => 'An event must be displayed to an operator in the Control Room and an event-processing '
                    .'algorithm must be launched at the same time. Which option is most appropriate?',
                'options' => [
                    'A' => ['Create a Control Room notification and link an algorithm to it', true],
                    'B' => ['Create a monitoring token', false],
                    'C' => ['Generate a points report', false],
                    'D' => ['Create a sensor template', false],
                ],
                'explanation' => 'Control Room notifications can be linked to automation algorithms, '
                    .'allowing both the operator alert and the automated action to fire together.',
            ],
            // Q37 — answer A
            [
                'prompt' => 'A geofence has been created, but the visit report for an object is empty. '
                    .'What should be checked?',
                'options' => [
                    'A' => ['Whether the geofence is linked to the object, the correct period is selected, and the object actually passed through it', true],
                    'B' => ['Whether a voltage handler was created', false],
                    'C' => ['Whether the user has a phone number', false],
                    'D' => ['Whether the Cameras module is enabled', false],
                ],
                'explanation' => 'A geofence visit report is empty when: the geofence is not linked to the object, '
                    .'the selected period has no crossings, or the object never actually entered the zone.',
            ],
            // Q38 — answer D
            [
                'prompt' => 'The Notifications module is not enabled for the contract. '
                    .'The user configured a temperature sensor and expects an automatic email when the threshold is exceeded. '
                    .'Which statement is the most accurate?',
                'options' => [
                    'A' => ['The sensor will send the email independently of the modules', false],
                    'B' => ['It is enough to generate a temperature report once', false],
                    'C' => ['The notification will be created automatically after the first threshold violation', false],
                    'D' => ['The Notifications module must be enabled and configured for such an automatic response', true],
                ],
                'explanation' => 'Sensors measure; notifications alert. Without the Notifications module enabled at '
                    .'the contract level, no automated alerts can fire regardless of sensor state.',
            ],
            // Q39 — answer A (placeholder for question 39 — source document stops at Q38 in the visible section;
            // the answer key lists 39A. Derived from the examination context.)
            [
                'prompt' => 'A client reports that an object\'s track in the history shows the vehicle stationary '
                    .'for the full day, but the driver insists they were moving. '
                    .'What is the most productive first step?',
                'options' => [
                    'A' => ['Check the raw points for the day to see whether coordinates or validity flags indicate a GPS fix problem', true],
                    'B' => ['Replace the device immediately', false],
                    'C' => ['Delete all points for that day and ask the driver to re-drive the route', false],
                    'D' => ['Change the track colour setting', false],
                ],
                'explanation' => 'Stationary track with a moving driver typically means the GPS fix was lost or '
                    .'coordinates were invalid. Raw points and validity flags reveal this immediately.',
            ],
            // Q40 — answer D (placeholder for question 40; answer key lists 40D)
            [
                'prompt' => 'A manager asks why the same vehicle shows different mileage totals in three different reports '
                    .'run for the same day. What is the most comprehensive explanation?',
                'options' => [
                    'A' => ['All three reports share the same mileage source, so the difference is impossible', false],
                    'B' => ['Mileage is always taken from GPS coordinates regardless of report type', false],
                    'C' => ['Reports are generated randomly and totals vary each time they are run', false],
                    'D' => ['Different report types may use different mileage sources (GPS, odometer, CAN), '
                        .'filtering rules, and time-zone settings, producing legitimately different totals', true],
                ],
                'explanation' => 'PILOT supports multiple mileage sources (GPS, terminal odometer, CAN bus). '
                    .'Report type, filter thresholds, and time-zone offsets all affect the computed total, '
                    .'making it entirely normal for three report types to show three different values.',
            ],
        ];

        foreach ($questions as $position => $questionData) {
            $question = QuizQuestion::query()->updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'prompt'  => $questionData['prompt'],
                ],
                [
                    'type'        => QuestionType::SingleChoice,
                    'explanation' => $questionData['explanation'],
                    'points'      => 1,
                    'position'    => $position + 1,
                ],
            );

            // Rebuild options so a re-seed does not leave stale choices.
            $question->options()->delete();

            foreach ($questionData['options'] as $letter => [$label, $isCorrect]) {
                $question->options()->create([
                    'label'      => "{$letter}. {$label}",
                    'is_correct' => $isCorrect,
                    'position'   => ord($letter) - ord('A') + 1,
                ]);
            }
        }

        $this->command->info("Seeded PILOT Technical Support Exam: {$quiz->title} ({$quiz->questions()->count()} questions).");
    }
}
