@ou @ou_vle @qtype @qtype_recordrtc
Feature: Test editing record audio and video questions
  As a teacher
  In order to be able to update my record audio and video questions
  I need to edit them

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
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name                   | template |
      | Test questions   | recordrtc | Record audio question  | audio    |

  @javascript
  Scenario: Requested time limit is validated
    When I am on the "Record audio question" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
      | Question name                  | Edited question name |
      | id_timelimitinseconds_number   | 11                   |
      | id_timelimitinseconds_timeunit | minutes              |
    And I press "id_submitbutton"
    Then I should see "Maximum recording duration cannot be greater than 10 mins."
    And I set the following fields to these values:
      | id_timelimitinseconds_number   | 0       |
      | id_timelimitinseconds_timeunit | seconds |
    And I press "id_submitbutton"
    And I should see "Maximum recording duration must be greater than 0."
    And I set the following fields to these values:
      | id_timelimitinseconds_number   | -10     |
      | id_timelimitinseconds_timeunit | seconds |
    And I press "id_submitbutton"
    And I should see "Maximum recording duration must be greater than 0."
    And I set the following fields to these values:
      | id_timelimitinseconds_number   | 15      |
      | id_timelimitinseconds_timeunit | seconds |
    And I press "id_submitbutton"
    And I should see "Edited question name"
