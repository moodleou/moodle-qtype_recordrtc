@ou @ou_vle @qtype @qtype_recordrtc
Feature: Test creating record audio and video questions
  As a teacher
  In order to test my students speaking skills
  I need to create a record audio and video question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | Mark      | Allright | teacher@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Create a record audio and video question
    When I add a "item_qtype_recordrtc" question filling the form with:
      | Question name                  | Record audio question                               |
      | Question text                  | <p>Please record yourself talking about Moodle.</p> |
      | General feedback               | <p>I hope you spoke clearly and coherently.</p>     |
      | id_defaultmark                 | 2                                                   |
      | id_mediatype                   | video                                               |
      | id_timelimitinseconds_number   | 15                                                  |
      | id_timelimitinseconds_timeunit | 1                                                   |
      | Allow pausing                  | Yes                                                 |

    Then I should see "Record audio question"
    # Checking that the next new question form displays user preferences settings.
    When I press "Create a new question ..."
    And I set the field "item_qtype_recordrtc" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
# Re-instate this when we no longer need to support 3.9 and 3.10.
#    Then the following fields match these values:
#      | id_defaultmark                 | 2     |
#      | id_mediatype                   | video |
#      | id_timelimitinseconds_number   | 15    |
#      | id_timelimitinseconds_timeunit | 1     |
#      | Allow pausing                  | Yes   |
