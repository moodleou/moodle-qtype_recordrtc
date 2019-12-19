@ou @ou_vle @qtype @qtype_recordrtc
Feature: Test creating record audio questions
  As a teacher
  In order to test my students speaking skills
  I need to create a record audio question

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
  Scenario: Create a record audio question
    When I add a "Record audio" question filling the form with:
      | Question name    | Record audio question                               |
      | Question text    | <p>Please record yourself talking about Moodle.</p> |
      | General feedback | <p>I hope you spoke clearly and coherently.</p>     |
    Then I should see "Record audio question"
