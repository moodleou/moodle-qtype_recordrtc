@ou @ou_vle @qtype @qtype_recordrtc
Feature: Test duplicating a quiz containing record audio and video questions
  As a teacher
  In order re-use my courses containing Record audio and video questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name                  | template | timelimitinseconds |
      | Test questions   | recordrtc | Record audio question | audio    | 42                 |
      | Test questions   | recordrtc | Record A/V question   | customav |                    |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz     |
    And quiz "Test quiz" contains the following questions:
      | Record audio question | 1 |

  @javascript
  Scenario: Backup and restore a course containing a record audio and video question
    When I am on the "Course 1" course page logged in as admin
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on the "Course 2" "core_question > course question bank" page
    And I choose "Edit question" action for "Record audio question" in the question bank
    Then the following fields match these values:
      | Question name                | Record audio question                               |
      | Question text                | <p>Please record yourself talking about Moodle.</p> |
      | General feedback             | <p>I hope you spoke clearly and coherently.</p>     |
      | id_timelimitinseconds_number | 42                                                  |
    And I press "Cancel"
    And I choose "Edit question" action for "Record A/V question" in the question bank
    Then the following fields match these values:
      | Question name             | Record A/V question                               |
      | id_feedbackfordevelopment | I hope you mentioned unit testing in your answer. |
