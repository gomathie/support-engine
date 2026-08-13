### **PILOT System Training Plan for 1st-Line Support (2 Weeks)**

Goal: To ensure a complete understanding of the architecture, core functions, and procedures of the PILOT system for effective handling of user requests.  
Format: Each day includes theoretical study of documentation sections and practical/test assignments.

---

### **Week 1: Basic Introduction and Core Operations**

Day 1: Introduction to PILOT and Basic Concepts

* Theory: Study sections: "About the Platform," "Basic PILOT Concepts and Terminology," "Roles and Access Rights."  
* Key Topics: What is PILOT, modular architecture, key terms (Object, Sensor, Contract, Account, Alert, Geofence), differences between roles (User, Administrator, Super Administrator).  
* Test Assignment:  
  1. Define the terms: Object, Sensor, Contract, Account.  
  2. Explain the difference between the "Personal Account" and the "Admin Panel."  
  3. What is a "Mapping Contract" and what is it used for? (Answer: A contract that combines data from several accounts in a single interface without directly altering the source data, e.g., for a security service).

Day 2: Interface and Navigation

* Theory: Study sections: "Interface Overview," "Top Panel," "Workspace," "Map," "Map Tools."  
* Key Topics: Interface structure (top panel, menu, workspace, map). Purpose of top panel elements (object statuses, notifications, balance, settings menu). Basic map tools (zoom, measure distance/area, select map type).  
* Practical Assignment (in test environment):  
  1. Log in to the system.  
  2. Find and describe what is displayed in each element of the top panel.  
  3. Change the map type to "Yandex Sat."  
  4. Use the tool to measure the distance between two arbitrary points.

Day 3: User and Rights Management

* Theory: Study sections: "Personnel and Groups," "Managing User Profiles," "User Access Rights."  
* Key Topics: Where users are created. How to fill out a user card (login, password, type). How to assign rights to objects ("Transport"), to labels ("Labels"), and system rights ("Rights").  
* Practical Assignment (in test environment \- perform in Admin Panel):  
  1. Create a new user with the "User" role.  
  2. Assign them rights to view 2 test objects.  
  3. Create a rights template for this role.  
  4. Block the created user, and then unblock them.

Day 4: Working with Objects (Part 1\)

* Theory: Study sections: "Adding an Object," "Object Card" (tabs "General," "Info").  
* Key Topics: What is an object. What information is mandatory for creating an object (Name, Device ID, Device Type). Where to find the device IMEI. Filling in basic data and vehicle passport data.  
* Practical Assignment (in test environment):  
  1. Manually create a new object (a car). Fill in the required fields and data in the "General" and "Info" sections (VIN, year of manufacture, etc.).  
  2. Assign it a tag and place it in a group.  
  3. Using the object menu (right-click), find the "Current Track" and "Follow Object" functions.

Day 5: Working with Objects (Part 2\) and the Object List

* Theory: Study sections: "Object List," "Object Groups," "Additional Object Information."  
* Key Topics: Filtering and sorting the object list. Configuring columns. Creating and using groups. Configuring color indicators for statuses. Searching for objects.  
* Practical Assignment (in test environment):  
  1. Configure color status indicators to your preference.  
  2. Create a new group "Test Vehicles" and move the previously created objects into it.  
  3. Configure the display of the following columns in the object list: "Name," "Speed," "Status," "Driver."  
  4. Filter the list to show only objects "in motion."

---

### **Week 2: Advanced Functions and Diagnostics**

Day 6: Sensors (Part 1\)

* Theory: Study sections: "Adding a Sensor," "Working with Sensors," "Sensor Types."  
* Key Topics: What is a sensor. Main types of sensors by purpose (ignition, fuel, temperature) and by principle of operation (discrete, two-position, impulse). Mandatory sensor parameters (Name, Field, Unit of measurement).  
* Practical Assignment (in test environment):  
  1. Add an ignition sensor (two-position) to an object. Check its operation via the "Points" tab.  
  2. Add a fuel level sensor (discrete). Fill in the main parameters.

Day 7: Sensors (Part 2\)

* Theory: Study sections: "Calibration Table," "Applying Formulas," "Sensor Templates."  
* Key Topics: What a calibration table is for. Simple conversion formulas (e.g., /1000 to convert millivolts to volts). Creating and applying sensor templates.  
* Practical Assignment (in test environment):  
  1. For the fuel sensor, set up a simple calibration table with 3-4 points.  
  2. For the battery voltage sensor, set the formula /1000.  
  3. Save the sensor configuration as a template and apply it to another test object.

Day 8: History and Reports

* Theory: Study sections: "History," "Reports," "Report List."  
* Key Topics: How movement history is built. What elements are displayed (track, player, events, graph). Types of reports. Report generation parameters (time, grouping, breakdown).  
* Practical Assignment (in test environment):  
  1. Build a movement history for an object for the last 24 hours. Use the player to review it.  
  2. Create a "Mileage and Stops" report for yesterday for one object.  
  3. Save the report in PDF and Excel formats.

Day 9: Contract Settings and Notifications

* Theory: Study sections: "Contract Settings," "Privacy," "Notification Settings," "Two-Factor Authentication."  
* Key Topics: Where contract settings are located. How to change a password, configure an email for notifications. How to enable and configure 2FA. Where to enable notification types (by email, SMS).  
* Practical Assignment (in test environment):  
  1. In the contract settings, add a test email and send a confirmation email.  
  2. In the "Notifications" section, activate receiving "Speed Limit Exceeded" notifications via email.  
  3. (If available) Enable and configure two-factor authentication for your test user.

Day 10: Additional Modules and Tools

* Theory: Review the "Modular Architecture" section. Study sections: "Report Scheduler," "Tokens," "Geofences" (general concept).  
* Key Topics: Which modules can be connected (Notifications, Drivers, Eco Driving). How to Set Up Automatic Scheduled Report Sending: What access tokens are for (viewing objects without account access).  
* Test Assignment:  
  1. Describe what the "Notifications" module is for and what is needed for it to work.  
  2. What is a "Token" and in what scenarios is it used?  
  3. What actions can a user perform using the "Report Scheduler"?

Day 11-12: Comprehensive Review and Call Simulation

* Theory: Review all studied sections. Analysis of common user issues.  
* Practical Assignment (Simulation):  
  * Scenario 1: A user cannot log in to the system. What diagnostic steps will you take? (Check login/password, check for a block, check IP filtering settings).  
  * Scenario 2: A user does not see an object in the list. What could be the reason? (No rights to the object, incorrect filtering, object is blocked).  
  * Scenario 3: A user is not receiving speed limit notifications. What needs to be checked? (Is the Notifications module connected, are alerts configured in the sensor, is the email confirmed, is the notification enabled in the contract settings).  
  * Scenario 4: A user wants to provide a partner with access to a map with 3 objects for 3 days without creating an account. How to do this? (Create a token with a limited validity period).

Day 13-14: Final Testing and Consultation

* Final Test: Written or practical test covering all key topics of the two weeks.  
* Error Review: Consultation on complex topics, Q\&A session.  
* Complex Case Resolution: Working with non-standard requests based on documentation.

---

📁 Additional Training Materials:

* Various demo videos on the portal: [https://www.youtube.com/@pilot.telematics/videos](https://www.youtube.com/@pilot.telematics/videos)  
* Link to the manual: [https://doc.pilot-gps.](https://doc.pilot-gps.ru/)com

