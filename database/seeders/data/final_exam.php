<?php

/*
 | Extracted from docs/PILOT_Technical_Support_Employee_Exam_EN2.md.
 |
 | Section A carries no correct answers: the source document's
 | "Answer Key for the Examiner" heading is present but empty. The options
 | are seeded as written and every question is flagged as needing a key,
 | so whoever holds the answers can tick them in the admin rather than
 | anyone guessing.
 |
 | Sections B and C are written answers, marked by a human.
 */

return [
    'section_a' => [
        [
            'number' => 1,
            'prompt' => 'An administrator created a user and specified a login, password, and email address. The user logged in but cannot see any objects. What is the most likely explanation?',
            'options' => [
                'A. The user has not been granted access rights to the objects',
                'B. The user has not confirmed the email address',
                'C. The objects have not been placed into a group',
                'D. A monitoring token has not been created for the user',
            ],
        ],
        [
            'number' => 2,
            'prompt' => 'A user must be given access to only two objects out of twenty. What is the most appropriate method?',
            'options' => [
                'A. Create a separate account and move the two objects there',
                'B. Create a token for the two objects and use it instead of a user account',
                'C. Grant the user rights only to the two required objects',
                'D. Hide the other objects using a filter in the object list',
            ],
        ],
        [
            'number' => 3,
            'prompt' => 'Several dispatchers need the same set of system permissions and access to the same list of objects. Which solution reduces the likelihood of errors?',
            'options' => [
                'A. Configure each user separately from memory',
                'B. Create a permissions template and apply it to the users',
                'C. Assign everyone the Chief Administrator role',
                'D. Create one shared login for all dispatchers',
            ],
        ],
        [
            'number' => 4,
            'prompt' => 'A user needs to allow an external contractor to monitor three objects for a limited period without sharing a login and password. What is the best option?',
            'options' => [
                'A. Create a new administrator',
                'B. Give the contractor the password of an existing user',
                'C. Export the object tracks to KML',
                'D. Create a monitoring token for the required objects with an expiration period',
            ],
        ],
        [
            'number' => 5,
            'prompt' => 'A user is blocked in the administrative panel. Which statement is the most accurate?',
            'options' => [
                'A. The user will not be able to log in, but the user’s objects will continue receiving data',
                'B. The user will not be able to log in, and all of the user’s objects will stop receiving data',
                'C. The account and activity history will be deleted immediately',
                'D. The block applies only to the mobile application',
            ],
        ],
        [
            'number' => 6,
            'prompt' => 'A user has the required rights to objects but cannot see a particular section of the system. What should be checked first?',
            'options' => [
                'A. Whether the objects have license plate numbers',
                'B. Whether the user has the required system permission for that section',
                'C. Whether tags have been assigned to the objects',
                'D. Whether at least one report has been generated',
            ],
        ],
        [
            'number' => 7,
            'prompt' => 'The correct IMEI was entered when creating an object, but the wrong device type was selected. What is the most likely result?',
            'options' => [
                'A. The object will necessarily appear twice',
                'B. Packets will not be accepted by the server',
                'C. Packets may be accepted, but the user may be unable to send commands',
                'D. The system will always detect the device type automatically from the IMEI',
            ],
        ],
        [
            'number' => 8,
            'prompt' => 'Packets from a device reach the server but are not linked to the created object. What should be checked first?',
            'options' => [
                'A. Whether the device ID in the object matches the identifier in the incoming packets',
                'B. Whether a geofence has been created around the vehicle',
                'C. Whether the user has permission to edit the device',
                'D. The server port',
            ],
        ],
        [
            'number' => 9,
            'prompt' => 'What is the main purpose of an object group compared with a tag?',
            'options' => [
                'A. A group stores raw data, while a tag stores processed data',
                'B. A group is used only in reports, while a tag is used only on the map',
                'C. There is no functional difference between a group and a tag',
                'D. A group organizes objects into a structure, while a tag provides additional classification and filtering',
            ],
        ],
        [
            'number' => 10,
            'prompt' => 'An object was moved from one contract to another. What risk should be considered?',
            'options' => [
                'A. The device IMEI will change automatically',
                'B. Module availability, user permissions, and contract-dependent settings may change',
                'C. The object’s raw data will necessarily be deleted',
                'D. The GPS terminal will need to be restarted',
            ],
        ],
        [
            'number' => 11,
            'prompt' => 'An additional module must be enabled for a contract. Where is the most logical place to do this?',
            'options' => [
                'A. In the contract settings of the administrative panel',
                'B. In the user settings',
                'C. In the object history',
                'D. In the user’s browser configuration',
            ],
        ],
        [
            'number' => 12,
            'prompt' => 'After an object is created, no data appears even though the ID and device type are correct. What is the most rational troubleshooting order?',
            'options' => [
                'A. Create reports first, then sensors, and then check the port',
                'B. First verify packet reception and the port, then verify packet-to-object binding',
                'C. First change the user password, then recreate the object',
                'D. First create a geofence and a loss-of-connection notification',
            ],
        ],
        [
            'number' => 13,
            'prompt' => 'A temperature sensor was created using the field temp_1, but the device sends only temperature in raw points. What will happen?',
            'options' => [
                'A. PILOT will automatically match similar field names',
                'B. The sensor will obtain the temperature from the coordinate packet',
                'C. The sensor will most likely not receive a value',
                'D. The value will appear after a temperature report is generated',
            ],
        ],
        [
            'number' => 14,
            'prompt' => 'What is the most reliable way to determine the field for a new sensor?',
            'options' => [
                'A. Inspect the object’s raw points and find the parameter with the expected values',
                'B. Choose the field with the most suitable name without checking the data',
                'C. Copy a field from any object in the same account',
                'D. Generate a report first and then choose the field from the report',
            ],
        ],
        [
            'number' => 15,
            'prompt' => 'The device sends a voltage value of 14592 mV. The value must be displayed in volts. Which configuration is correct?',
            'options' => [
                'A. Unit: mV, formula: *1000',
                'B. Unit: V, formula: /1000',
                'C. Unit: V, calibration: 14592 → 14592',
                'D. Unit: %, formula: /100',
            ],
        ],
        [
            'number' => 16,
            'prompt' => 'When is a formula preferable to a calibration table?',
            'options' => [
                'A. When the relationship is known and can be expressed by a simple mathematical transformation',
                'B. When an input value must be mapped to arbitrary text states',
                'C. When a fuel tank has a complex nonlinear shape',
                'D. When several experimental fuel-level points must be converted into liters',
            ],
        ],
        [
            'number' => 17,
            'prompt' => 'When is a calibration table preferable to a simple formula?',
            'options' => [
                'A. When millivolts must be divided by 1000',
                'B. When the input and output values are always equal',
                'C. When the relationship between the input signal and the physical quantity is nonlinear',
                'D. When the user does not have access to sensors',
            ],
        ],
        [
            'number' => 18,
            'prompt' => 'When does editing the calibration table of a new sensor become available?',
            'options' => [
                'A. Before selecting the sensor field',
                'B. Only after the new sensor has been saved',
                'C. Only after a report has been generated',
                'D. After creating a user template',
            ],
        ],
        [
            'number' => 19,
            'prompt' => 'A fuel sensor calibration table contains the following points: 0 → 0 L; 1000 → 20 L; 2000 → 50 L; 3000 → 80 L. If the input is between 1000 and 2000, what principle is used to determine the intermediate value?',
            'options' => [
                'A. Random selection of the nearest point',
                'B. Resetting to zero until an exact table value is received',
                'C. Using the previous row value without calculation',
                'D. Calculating a value between neighboring calibration points',
            ],
        ],
        [
            'number' => 20,
            'prompt' => 'A fuel sensor shows a plausible current value, but the report contains false refuelings and drains. What is the most justified conclusion?',
            'options' => [
                'A. Because the current value is correct, the sensor configuration is guaranteed to be correct',
                'B. The raw value dynamics, calibration, and refueling/drain detection parameters must be checked',
                'C. The sensor should be deleted immediately and the object recreated',
                'D. The only possible cause is a faulty GPS antenna',
            ],
        ],
        [
            'number' => 21,
            'prompt' => 'The same raw voltage transformation is used for several object models. Which mechanism is intended for reusing common processing logic?',
            'options' => [
                'A. Sensor handler',
                'B. Monitoring token',
                'C. User group',
                'D. Report scheduler',
            ],
        ],
        [
            'number' => 22,
            'prompt' => 'After changing a sensor formula, the current value became correct, but an old report for the previous month did not change. What action will most likely be required?',
            'options' => [
                'A. Log in again as another user',
                'B. Rename the sensor',
                'C. Recalculate the data for the required historical period',
                'D. Change the map type',
            ],
        ],
        [
            'number' => 23,
            'prompt' => 'For the DIN1 discrete input, the states “Equipment Off” and “Equipment On” must be displayed. What is the most suitable solution?',
            'options' => [
                'A. Create a mileage sensor with a /1000 formula',
                'B. Create a two-state sensor and configure the input-state mapping',
                'C. Create a temperature sensor with calibration in liters',
                'D. Use longitude as the sensor field',
            ],
        ],
        [
            'number' => 24,
            'prompt' => 'An ignition sensor was created using DIN1. The points show DIN1=1, but the sensor displays “Off.” What should be checked first?',
            'options' => [
                'A. The mapping between input values and the On/Off states',
                'B. The account name',
                'C. The object’s geographic address',
                'D. The PDF report format',
            ],
        ],
        [
            'number' => 25,
            'prompt' => 'How do raw points differ from the displayed track?',
            'options' => [
                'A. Raw points already contain only the filtered route',
                'B. The track is stored in the device, while points are created by the user',
                'C. Raw points are the received data, while the track is the result of processing and displaying those data',
                'D. There is no difference',
            ],
        ],
        [
            'number' => 26,
            'prompt' => 'A single jump of several kilometers followed by an immediate return is visible on the track. What is the most informative first step?',
            'options' => [
                'A. Delete the object',
                'B. Inspect the corresponding points, time, coordinates, speed, and validity flag',
                'C. Run a track recalculation',
                'D. Change the object filter settings',
            ],
        ],
        [
            'number' => 27,
            'prompt' => 'After applying a filter, the track looks correct. Which conclusion must not be made?',
            'options' => [
                'A. The filter may exclude erroneous points from route construction',
                'B. The user-facing display became clearer',
                'C. Filter settings affect the history-building result',
                'D. The original cause of the erroneous coordinates has been fixed on the device',
            ],
        ],
        [
            'number' => 28,
            'prompt' => 'A user selected a history period but cannot see a track. Which combination of causes is the most realistic?',
            'options' => [
                'A. No object was selected, the search was not run, or the period contains no suitable data',
                'B. No fuel calibration table was created',
                'C. The user did not add a vehicle photo',
                'D. The ignition sensor does not work',
            ],
        ],
        [
            'number' => 29,
            'prompt' => 'Why is it important to use the same time period and time zone when comparing a track and a report?',
            'options' => [
                'A. Otherwise the user will automatically lose permissions',
                'B. Otherwise the system will select a different device type',
                'C. Otherwise the compared intervals and day boundaries may not match',
                'D. Otherwise the IMEI will change',
            ],
        ],
        [
            'number' => 30,
            'prompt' => 'Movement is shown in the history, but the ignition sensor is always Off. Which statement is the most accurate?',
            'options' => [
                'A. Movement automatically proves that the ignition sensor is configured correctly',
                'B. Coordinate movement and sensor state are different data; the field and sensor logic must be checked',
                'C. The map should be zoomed in',
                'D. Only the PDF report needs to be rebuilt',
            ],
        ],
        [
            'number' => 29,
            'prompt' => 'The temperature report is empty even though temperature is present in the raw points. What is the most logical step?',
            'options' => [
                'A. Check that a temperature sensor exists and is configured correctly, and that the report can access it',
                'B. Restart PostgreSQL immediately',
                'C. Delete the raw points and receive them again',
                'D. Create a geofence',
            ],
        ],
        [
            'number' => 30,
            'prompt' => 'An equipment-operation report is generated, but all intervals have zero duration. What should be checked?',
            'options' => [
                'A. The object icon color',
                'B. Correct sensor state transitions and timestamps in the input data',
                'C. The presence of a monitoring token',
                'D. The partner name',
            ],
        ],
        [
            'number' => 31,
            'prompt' => 'What is the main difference between the Report Builder and running a predefined report?',
            'options' => [
                'A. The Report Builder receives data directly from the GPS terminal',
                'B. Predefined reports are available only to administrators',
                'C. The Report Builder allows a custom structure and custom output fields to be created',
                'D. The Report Builder is used only to change passwords',
            ],
        ],
        [
            'number' => 32,
            'prompt' => 'A user changed the set of report columns but cannot see the required metric. What is the most likely explanation?',
            'options' => [
                'A. The selected report type does not provide that data field',
                'B. The object has no sensors',
                'C. The user permissions are not configured',
                'D. No partner has been created',
            ],
        ],
        [
            'number' => 33,
            'prompt' => 'The same report must be sent automatically every morning. Which tool is most suitable?',
            'options' => [
                'A. Report Scheduler',
                'B. Calibration table',
                'C. Monitoring token',
                'D. Current Track menu',
            ],
        ],
        [
            'number' => 34,
            'prompt' => 'A temperature notification is configured for objects with a specific tag. A new object has the required temperature sensor, but the notification does not trigger for it. What should be checked first?',
            'options' => [
                'A. Whether the object has the tag used by the notification',
                'B. Whether a token was created for the object',
                'C. Whether the object appears in a mileage report',
                'D. Whether its icon color matches the other objects',
            ],
        ],
        [
            'number' => 35,
            'prompt' => 'An email notification is configured correctly and the event is recorded in the system, but the user does not receive the email. Which check is most appropriate?',
            'options' => [
                'A. Whether the recipient email is confirmed and the Email channel is selected',
                'B. Whether a fuel calibration table exists',
                'C. Whether the object has a VIN',
                'D. Whether the track filter is configured',
            ],
        ],
        [
            'number' => 36,
            'prompt' => 'An event must be displayed to an operator in the Control Room and an event-processing algorithm must be launched at the same time. Which option is most appropriate?',
            'options' => [
                'A. Create a monitoring token',
                'B. Create a Control Room notification and link an algorithm to it',
                'C. Generate a points report',
                'D. Create a sensor template',
            ],
        ],
        [
            'number' => 37,
            'prompt' => 'A geofence has been created, but the visit report for an object is empty. What should be checked?',
            'options' => [
                'A. Whether the geofence is linked to the object, the correct period is selected, and the object actually passed through it',
                'B. Whether a voltage handler was created',
                'C. Whether the user has a phone number',
                'D. Whether the Cameras module is enabled',
            ],
        ],
        [
            'number' => 38,
            'prompt' => 'The Notifications module is not enabled for the contract. The user configured a temperature sensor and expects an automatic email when the threshold is exceeded. Which statement is the most accurate?',
            'options' => [
                'A. The sensor will send the email independently of the modules',
                'B. It is enough to generate a temperature report once',
                'C. The notification will be created automatically after the first threshold violation',
                'D. The Notifications module must be enabled and configured for such an automatic response',
            ],
        ],
    ],
    'section_b' => [
        [
            'number' => 1,
            'title' => 'Configuring a New Object Based on Actual Data',
            'prompt' => 'A new object has been added to the system. The device is already transmitting coordinates and the following parameters:
DIN1=1
ain1=12450
fuel=1820
temp_1=23.7
Describe how you would determine the purpose of each parameter and which sensors you would create. For each sensor, specify:
type;
field;
unit of measurement;
whether a formula or calibration is required;
how the result will be verified.',
        ],
        [
            'number' => 2,
            'title' => 'Selecting a Mileage Source',
            'prompt' => 'The client wants to see accurate vehicle mileage. The device transmits GPS coordinates, the terminal odometer, and CAN mileage. Explain:
which mileage sources are available;
how they differ;
which source you would select;
what must be checked before the final configuration;
when different sources may show different values.',
        ],
        [
            'number' => 3,
            'title' => 'Comparing Data in Points, Graphs, and Reports',
            'prompt' => 'The client reports that the temperature shown in the object card, in a history point, and in the report for the same moment is different. Describe possible reasons and the troubleshooting sequence. Consider:
the raw value;
the formula;
the calibration table;
packet time;
time zone;
recalculations;
report data grouping.',
        ],
        [
            'number' => 4,
            'title' => 'Configuring Multiple Temperature Sensors',
            'prompt' => 'A refrigerated vehicle has three temperature sensors:
cargo body temperature;
refrigeration unit temperature;
outside air temperature.
Describe how to configure them so that:
the values are easy to distinguish;
each sensor is visible in history;
they can be compared on one graph;
a report can be generated for each sensor;
a notification triggers only for cargo body temperature.',
        ],
        [
            'number' => 5,
            'title' => 'Creating a Custom Report',
            'prompt' => 'The client requires a report with the following columns:
object;
date;
work start;
work end;
work duration;
maximum temperature;
average temperature;
mileage;
number of visited geofences.
Describe how to determine whether such a report can be created in the Report Builder, which data sources are required, and how you would verify the result.',
        ],
        [
            'number' => 6,
            'title' => 'Complex Notification with Multiple Conditions',
            'prompt' => 'The client wants a notification when all of the following conditions are true at the same time:
ignition is on;
the object is outside the permitted geofence;
speed exceeds 80 km/h;
the violation continues for at least two minutes.
Describe how you would attempt to implement this condition. Specify the sensors, geofences, restrictions, delays, and recipients that must be configured.',
        ],
        [
            'number' => 7,
            'title' => 'Route-Control Configuration',
            'prompt' => 'A vehicle must travel daily between a warehouse and a retail location only along an approved route. Describe how geofences, notifications, and reports can be used to control:
visits to the starting and ending points;
route deviation;
arrival time;
duration of stay;
failure to visit a mandatory point.',
        ],
        [
            'number' => 8,
            'title' => 'Creating a Contract for a New Client',
            'prompt' => 'A new contract must be created for a company with ten vehicles. Describe the complete setup procedure:
core details;
account type;
tariff;
currency;
modules;
configurations;
user;
objects;
access verification after setup.
Specify which setup errors may become visible only after the client logs in to the user interface.',
        ],
        [
            'number' => 9,
            'title' => 'Moving Objects Between Contracts',
            'prompt' => 'Five vehicles must be moved from one contract to another. Their history and sensor settings must be preserved, the new user must receive access, and old users must lose access. Describe:
pre-move checks;
the move procedure;
what happens to historical data;
which permissions and modules must be checked afterward;
how to confirm that old users no longer see the objects.',
        ],
        [
            'number' => 10,
            'title' => 'Configuring a Partner',
            'prompt' => 'A new partner is being created and will independently manage its contracts and users. Describe which parameters must be configured:
partner details;
currency;
tariff;
permissions;
SMTP;
available modules;
rebranding;
two-factor authentication.
Explain which settings belong to the partner and which must be configured separately in each contract.',
        ],
        [
            'number' => 11,
            'title' => 'Prepayment and Financial Blocking',
            'prompt' => 'A contract uses prepayment. The client balance became negative, but some objects are still visible and continue transmitting data. Describe:
how financial blocking should work;
which contract settings must be checked;
how contract blocking differs from object blocking;
what remains available to the administrator;
how service is restored after a payment is received.',
        ],
        [
            'number' => 12,
            'title' => 'Contract and Object Configurations',
            'prompt' => 'Explain the difference between:
contract settings;
enabled modules;
contract configurations;
individual object configurations;
object sensor settings.
Provide one practical example for each level and explain why a setting made incorrectly at one level may not be corrected by changing another level.',
        ],
        [
            'number' => 13,
            'title' => 'Eco Driving Module',
            'prompt' => 'The client enabled Eco Driving and wants to evaluate drivers based on:
harsh acceleration;
harsh braking;
speeding;
dangerous cornering;
engine idling.
Describe:
which source data are required;
which criteria must be configured;
how violations are generated;
which reports or ratings the client will receive;
why identical settings may behave differently for trucks and passenger vehicles.',
        ],
        [
            'number' => 14,
            'title' => 'Commands Module',
            'prompt' => 'The client wants to send configuration and engine-blocking commands to the terminal. Describe:
which conditions must be met before a command can be sent;
how the device type is related to the list of available commands;
how to verify that the command was sent to the device;
how sending a command differs from actual execution;
what to do when the command has the Sent status but no acknowledgement is received from the device.',
        ],
        [
            'number' => 15,
            'title' => 'GARM / Control Room Module',
            'prompt' => 'Operator work with critical events must be configured for:
panic-button activation;
prolonged temperature threshold violation;
exit from a mandatory geofence;
loss of external power;
equipment emergency state.
Describe how to organize the workflow in the module:
create event sources;
configure notifications;
deliver the event to the Control Room;
assign a responsible operator;
process and close the event;
record comments;
monitor unprocessed events;
generate reporting on operator actions.',
        ],
    ],
    'section_c' => [
        [
            'number' => 1,
            'title' => 'Retransmission Configuration',
            'prompt' => '1. Locate the Source Object
Find object ID 125150 on the Cream server.
Show the object card.
Verify the object’s core information.
2. Configure Hidden Retransmission
Create a hidden retransmission for the object.
Select the Wialon Retranslator protocol.
Configure data transmission to test server 93.90.84.90.
Save the retransmission.
Show its settings to the examiner.
Explain which parameters are required for retransmission to work.',
        ],
        [
            'number' => 2,
            'title' => 'Partner Creation',
            'prompt' => '3. Create a New Partner
Create a new partner in the administrative panel.
Set the partner tariff to USD 16 per month.
Set the account type to postpaid.
Fill in all mandatory partner details.
4. Enable Partner Modules
Enable the following modules for the partner: Geofences, Notifications, Commands, Cameras, Drivers, Logistics, and Service.
5. Configure Cameras and Partner SMTP
Set the camera tariff to USD 3.
Enable SMTP.
Use mx.pilot.tm as the mail server.
Use your work email address.
Show the SMTP settings to the examiner.
Demonstrate how mail delivery can be tested.',
        ],
        [
            'number' => 3,
            'title' => 'Contract Creation',
            'prompt' => '6. Create a Contract
Create a new contract under the partner.
Set the contract tariff to USD 55.
Set the account type to Postpaid Light.
Fill in all mandatory contract details.
7. Enable Contract Modules
Enable all modules that were enabled for the partner: Geofences, Notifications, Commands, Cameras, Drivers, Logistics, and Service.
8. Configure the Video Server
Configure access to video server video.pilot-gps.com for the contract.
Show where the video server is specified.
Verify that the Cameras module is available in the contract.',
        ],
        [
            'number' => 4,
            'title' => 'Users and Permissions',
            'prompt' => '9. Create an Administrator
Create a user with administrator rights.
Grant full access within the created contract.
Verify access to objects and modules.
10. Create a Standard User
Create a regular user.
Restrict the user’s rights.
Grant view-only permissions.
Prohibit modification of objects, sensors, users, and settings.
Log in as the user to verify the permissions.
Show the differences between administrator and standard-user permissions.',
        ],
        [
            'number' => 5,
            'title' => 'Object Creation and Configuration',
            'prompt' => '11. Obtain the IMEI
Obtain the device IMEI from the retransmission data.
Show the packet from which the IMEI was obtained; locate the XML log.
Explain how the device identifier was determined.
12. Create the Object
Create an object in the new contract using the obtained IMEI.
Select the correct device type.
Fill in the mandatory object details.
Create a new folder for the object.
Verify incoming data.
Show the object on the map.
Show the object points.',
        ],
        [
            'number' => 6,
            'title' => 'Sensor Configuration',
            'prompt' => '13. Create Sensors
Configure the following sensors: ignition, fuel level, external power, speed, mileage (enable CAN mileage for the object), engine temperature, fuel consumption, and engine RPM.
For each sensor: determine the field from the device data; select the sensor type; set the unit; configure a formula or calibration table if required; verify the current value; show the value in the object points; show which reports use the sensor.',
        ],
        [
            'number' => 7,
            'title' => 'Geofences',
            'prompt' => 'General Requirement
Create all geofences in the Moscow Ring Road area.
14. Simple Geofence
Create a simple geofence.
Configure an entry notification.
Configure an exit notification.
Link the notifications to the created object.
15. Circular Geofence
Create a circular geofence.
Use it in the Logistics module configuration.
16. Linear Geofence
Create a linear geofence.
Configure it as a toll road.
Show the completed configuration to the examiner.',
        ],
        [
            'number' => 8,
            'title' => 'Notifications',
            'prompt' => '17. Ignition Notification
Create a notification based on the ignition sensor.
Configure triggering when ignition is on.
Link it to the created object.
Configure recipients.
Configure the delivery method.
Show the notification log.
18. Speed Notification
Create a notification based on the speed sensor.
Configure triggering above 90 km/h.
Link it to the created object.
Configure recipients.
Configure the delivery method.
Show the completed configuration.',
        ],
        [
            'number' => 9,
            'title' => 'Logistics Module',
            'prompt' => '19. Configure the Object for Logistics
Add the created object to the Logistics module.
Configure it for use in logistics tasks.
Verify that it is available when creating a task.
20. Create a Warehouse and Counterparty
Create a warehouse in Moscow.
Create a counterparty in Moscow.
Fill in all required warehouse and counterparty data.
21. Create a Logistics Task
Create a task for the object to depart from the warehouse.
Add a visit to the counterparty.
Specify the visit sequence.
Assign the created object.
Configure the task execution time.
Show the created task.
22. Prohibited Geofence
Use the circular logistics geofence for the object.
Show where the prohibition is configured.
Explain how the system controls visits to the prohibited area.
23. DaData Integration
Enable DaData in the Logistics module.
Use the provided test key.
Show where the key is entered.
Test address search through DaData.
Use the result when configuring the warehouse or counterparty.',
        ],
        [
            'number' => 10,
            'title' => 'Service Module',
            'prompt' => '24. Configure Maintenance
Enable the Service module for the object.
Configure maintenance every 5,000 km.
Show the current mileage and remaining mileage until maintenance.
25. Configure Consumables
Add consumables used during maintenance.
Specify quantities and units.
Link the consumables to the created maintenance item.
Show the final maintenance card.',
        ],
        [
            'number' => 11,
            'title' => 'Commands Module',
            'prompt' => '26. Engine Lock Command Template
Create an engine-lock command template.
Specify command code arm.
Link the template to the created object.
Show the command in the user interface.
27. Engine Unlock Command Template
Create an engine-unlock command template.
Specify command code disarm.
Link the template to the created object.
Show the command in the user interface.
28. Verify Commands
Show the command-sending log.
Explain the difference between sending a command and the device actually executing it.',
        ],
        [
            'number' => 12,
            'title' => 'Cameras',
            'prompt' => '29. Configure Camera Channels
Configure two camera channels on the object.
Select CMS as the type for both channels.
Show the settings of each channel.
30. Additional Camera ID
Add additional ID 3311231231.
Set its type to CMS.
Verify that the additional ID is displayed in the object settings.
Show where the Media section is located and what can be checked there.',
        ],
        [
            'number' => 13,
            'title' => 'Report Builder',
            'prompt' => '31. Create a Report
Create a Summary Table report in the Report Builder.
Configure the following output: date, object name, daily mileage, fuel level at the beginning of the day, fuel level at the end of the day, daily fuel consumption, and average engine-temperature sensor value.
32. Verify the Report
Configure daily data splitting.
Select the created object.
Generate the report.
Show which sensors provide the values.
Verify that all columns are populated.
Save the report.
Export the result.',
        ],
        [
            'number' => 14,
            'title' => 'Database Operations',
            'prompt' => 'General Requirement
Perform all changes in the test database. Before changing each record, find it, show the current value, make the change, and verify the result in the interface.
33. Change the Partner Tariff
Find the created partner in the database.
Change the tariff from USD 16 to USD 19.
Verify the change in the administrative panel.
34. Rename the Speed Sensor
Find the created object’s speed sensor in the database.
Change its name.
Verify the new name in the object card.
35. Rename the Notification
Find the ignition-on notification.
Change its name.
Verify that the notification conditions were preserved.
Verify the new name in the user interface.
36. Change the User Login
Find the created standard user.
Change the login.
Verify login with the new username.
Make sure the user permissions were preserved.
37. Rename the Object Folder
Find the folder containing the created object.
Change the folder name.
Verify that the object remains in the folder.
Verify the new name in the system.',
        ],
        [
            'number' => 15,
            'title' => 'Test Server Operations',
            'prompt' => '38. Locate the Port
Connect to the test server.
Find the port configured for retransmission.
Show the port configuration.
Show the process listening on the port.
39. Enable Port Debugging
Enable debug mode for the located port.
Show where debugging is enabled.
Verify that data arrive on the port.
Show a data packet.
Find the IMEI in the packet.
Disable debugging after the check.',
        ],
        [
            'number' => 16,
            'title' => 'Manual Recalculations',
            'prompt' => '40. Locate Recalculation Jobs in crontab
Open crontab.
Find the track recalculation.
Find the discrete-sensor recalculation.
Find the equipment-sensor recalculation.
Explain the purpose of each entry.
Show the full paths to the scripts.
41. Run Recalculations Manually
Prepare and demonstrate three manual recalculation commands for the last 10 days: tracks, discrete sensors, and equipment sensors.
For each command: use the real paths and parameters from crontab; redirect output and errors to a separate file; run the command after examiner approval; show the running process; show the final log.',
        ],
        [
            'number' => 17,
            'title' => 'Server Logs',
            'prompt' => '42. PHP-FPM Logs
Show where the PHP-FPM logs are located.
Specify the full path.
Show current log entries.
Show the PHP-FPM service status.
Name other services you know.
43. Manual Recalculation Directory
Show the directory containing manual recalculations.
Specify the full path.
Show the directory contents.
Show the full paths to geofence, Eco Driving, and driver recalculations.
44. Database Logs for Thursday
Determine where database logs are located.
Specify the full path or system service name.
Find entries for the most recent past Thursday.
Show a command that displays logs only for that day.
Show the result to the examiner.',
        ],
        [
            'number' => 18,
            'title' => 'Retransmission Debugging',
            'prompt' => '45. Enable Retransmission Debugging
Show the retransmission configuration file.
Specify the full path.
Show the parameter responsible for verbose logging.
Explain which identifier must be added.
Add the required object to verbose logging.
Validate the configuration file.
Show which service must be restarted.
Restart the required service.
Show the detailed retransmission log.
Specify the full path to the log.
Disable verbose logging after the check.
Restart the service again.
Confirm that debugging is disabled.
Final Demonstration
After completing the tasks, the examinee must demonstrate the following in sequence:
The source object on the Cream server.
The hidden Wialon Retranslator retransmission.
The created partner.
The partner tariff.
The partner’s enabled modules.
Camera and SMTP settings.
The created contract.
The contract account type and tariff.
Video server settings.
Two users and the difference in their permissions.
The created object with incoming data.
All configured sensors.
Three geofences.
Two notifications.
Logistics settings.
The warehouse, counterparty, and logistics task.
The prohibited geofence.
DaData settings.
Maintenance and consumables.
Two command templates.
Two CMS camera channels.
The additional camera ID.
The Summary Table report.
Changes made through the database.
The data-reception port and its debug mode.
Three manual recalculations.
Separate recalculation log files.
The full path to the PHP-FPM logs.
The full path to the manual recalculation directory.
Database logs for Thursday.
Retransmission debug settings.
Confirmation that all enabled debug modes have been disabled.
| No. | Answer | No. | Answer | No. | Answer | No. | Answer |
| 1 | A | 11 | A | 21 | A | 31 | A |
| 2 | C | 12 | B | 22 | C | 32 | B |
| 3 | B | 13 | C | 23 | B | 33 | C |
| 4 | D | 14 | A | 24 | A | 34 | A |
| 5 | A | 15 | B | 25 | C | 35 | A |
| 6 | B | 16 | A | 26 | B | 36 | A |
| 7 | C | 17 | C | 27 | D | 37 | A |
| 8 | A | 18 | B | 28 | A | 38 | B |
| 9 | D | 19 | D | 29 | C | 39 | A |
| 10 | B | 20 | B | 30 | B | 40 | D |
| Criterion | Points |
| Understanding of the task | 1 |
| Completeness of listed settings and checks | 1 |
| Correct sequence of actions | 1 |
| Method for verifying the result | 1 |
| Clarity and technical accuracy of the answer | 1 |
| Result | Assessment |
| 85–100 | Excellent understanding of the system |
| 70–84 | Good understanding with isolated knowledge gaps |
| 55–69 | Basic knowledge is present, but improvement is required |
| Below 55 | Section not passed |',
        ],
    ],
];
