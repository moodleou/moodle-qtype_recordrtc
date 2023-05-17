# Change log for the Record audio and video question type

## Changes in 1.7

* Now, if you have multiple recording questions on one page, students are prevented from
  starting recording in two questions simultaneously.
* The upgrade code was improved, to be more robust in the face of bad data in the database.


## Changes in 1.6

* The question type can now be used to make screen recordings, in addition to audio and video.
* During recording, we now show both the length of the recording so far, and the time left
  before reaching the limit, with a progress-bar-like display.
* The way audio recordings are converted to MP3 has been changed. This now happens as a
  separate stage after the recording has been made. This is more reliable, but adds a short delay.
  (Why are we using MP3 format? Because that is what the OU needs. When we get the time,
  we are planning add an option to select which format to use.)
* There is a capability to control whether users are allowed to download recordings.
* The question text is now validated when the 'Update the form' button is clicked.
* The form now has option to control what happens when used with the Self-assessment question behaviour.
* Admin imposed time-limit now cannot be bypassed by a question setting.
* Better display of what was recorded in the response history.


## Changes in 1.5

* New feature to allow recording to be paused in the middle (if the teacher wants it).
* New option to have bits of feedback which are displayed beside each input.
* Improved display of the placeholders people might want to add to the question text.
* Fixed a bug where the glossary filter could mess up the place-holders.
* Improve the help text.
* This version works with Moodle 4.0.


## Changes in 1.4

* Improvement to the user flow when staring a video recording. Now, the first click on the button is
  'Start camera', which just turns the camera on, so it can auto-focus and do it's white balance, and so you
  can check you are happy with what you are seeing. Then you click again to 'Start recording' when you are ready.
* When creating a question with multiple inputs, the question author can set a different time limit for each.
* And, the admin can set a different maximum allowed time limit for audio and video.
* We tie into the changes in Moodle 3.11+ where, when you create a new question, it remembers the options you
  selected last time, and uses the same option for the new question, making it quicker to create a lot of
  similar questions.
* Various improvements to the on-screen text and help.
* Fixed a bug leading to the Notice "currentcontext passed to repository::get_instances was not a context object".


## Changes in 1.3

* This question type now supports video recording as well as audio.
* When reviewing their attempt, students now get a link to download their recording.
* Improvements to the layout.
* Fixes, to prevent students submitting until the recording is complete.
* An improved icon.


## Changes in 1.2

* Fix spelling mistake.


## Changes in 1.1

* You can now have multiple separate audio recorders in one
  question. Good for creative language questions, for example
  a simulated dialogue.
* Various improvements to the usability, and styling, including
  disabling the Check button while recording is in progress.
* Fix the bug where you could input a negative 'Maximum duration'.
* Fix warnings when you tried to close the question preview pop-up.


## Changes in 1.0

* Maximum recording length can be set by the question author.
* Better keyboard focus handling when you start and stop recording.
* Fixed a bug with detecting whether the question type can work in the current browser.


## Changes in 0.9.1

* Changes required to work with the new self-assessment question behaviour.
* Including more accurate handling of the behaviour when the student does not submit a file.


## Changes in 0.9 beta

* First (beta) version of this plugin.
* Supports only recording audio, and there are no question settings
  beyond the standard ones.
* Warning, not tested yet, apart from the automated tests and some basic
  developer testing.
