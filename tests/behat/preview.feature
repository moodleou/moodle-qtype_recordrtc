@ou @ou_vle @qtype @qtype_recordrtc @_switch_window @javascript
Feature: Preview record audio questions
  As a teacher
  In order to check record audio questions will work for students
  I need to preview them

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

  Scenario: Preview a question and try to submit nothing.
    When I choose "Preview" action for "Record audio question" in the question bank
    And I switch to "questionpreview" window
    Then I should see "Please record yourself talking about Moodle."
    And I press "Save"
    And I should see "Not yet answered"
    And I press "Submit and finish"
    And I should see "Not answered"
    And I should see "I hope you spoke clearly and coherently."
    And I switch to the main window

  Scenario: Preview a question and submit a recording.
    When I choose "Preview" action for "Record audio question" in the question bank
    And I switch to "questionpreview" window
    # TODO find a way to simulate responding.
    And I switch to the main window
