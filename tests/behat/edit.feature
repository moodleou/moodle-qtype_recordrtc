@ou @ou_vle @qtype @qtype_recordrtc
Feature: Test editing record audio questions
  As a teacher
  In order to be able to update my record audio questions
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
      | questioncategory | qtype     | name                  | template |
      | Test questions   | recordrtc | Record audio question | audio    |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Edit record audio question
    When I choose "Edit question" action for "Record audio question" in the question bank
    And I set the following fields to these values:
      | Question name                  | Edited question name |
      | id_timelimitinseconds_number   | 11                   |
      | id_timelimitinseconds_timeunit | minutes              |
    And I press "id_submitbutton"
    Then I should see "The time limit cannot be greater than 10 mins."
    And I set the following fields to these values:
      | id_timelimitinseconds_number   | 0       |
      | id_timelimitinseconds_timeunit | seconds |
    And I press "id_submitbutton"
    Then I should see "The time limit cannot be zero."
    And I set the following fields to these values:
      | id_timelimitinseconds_number   | 15      |
      | id_timelimitinseconds_timeunit | seconds |
    And I press "id_submitbutton"
    Then I should see "Edited question name"
