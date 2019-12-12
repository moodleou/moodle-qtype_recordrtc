Question type RecordRTC
----------------------
RecordRTC is an audio question type providing students with an audio recording widget which collects students responses.
Students record their answers, listen to their recording, rerecord if needed, and submit thier recorded message as their response to the question.

### Who should use
This is one alternative start for devloping a question type plug in and is working code as is. Although it doesn't do any actual
grading or collect student input at all.

### Settings 
Go to setings.php and set the time of the recording for this questio

###Use
* Copy or rename the module directory to YOURQTYPENAME.
* Replace all occurances of YOURQTYPENAME in files with the new name for your question type.
* Rename files that have YOURQTYPENAME replacing YOURQTYPENAME with the new name for your question type.
* Replace '@copyright  THEYEAR YOURNAME (YOURCONTACTINFO)' with something like @copyright  2016 Marcus Green (mgreen@example.org)
* See http://docs.moodle.org/dev/Question_types for more info on how to create a question type plug in. Please add to it where
 you can.
