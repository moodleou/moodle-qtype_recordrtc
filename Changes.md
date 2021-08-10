# Change log for the Record audio and video question type

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
