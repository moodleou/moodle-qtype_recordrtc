@ou @ou_vle @qtype @qtype_recordrtc
Feature: Test record audio and video questions in report answer sheet
  As a teacher
  In order to test my students responses in report answer sheet
  I need to create a record audio and video question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher  | The       | Teacher  |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity | name   | intro              | course | idnumber | preferredbehaviour |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | quiz1    | interactive        |
      | quiz     | Quiz 2 | Quiz 2 description | C1     | quiz2    | deferredfeedback   |
    And the following "questions" exist:
      | questioncategory | qtype     | name | questiontext   | template |
      | Test questions   | recordrtc | RTC1 | First question | audio    |
    And quiz "Quiz 1" contains the following questions:
      | question | page |
      | RTC1     | 1    |

    @javascript
    Scenario: Sheet's special message for un-submitted RecordRTC question type
      # We need to work around for this case because Moodle is creating response file for current logged in user.
      Given the quiz_answersheets plugin is installed
      When I am on the "quiz1" "Activity" page logged in as "student1"
      And I click on "Attempt quiz" "button"
      And "student1" has recorded "moodle-sharon.ogg" into the record RTC question
      And I press "Finish attempt ..."
      And I press "Submit all and finish"
      And I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
      And I log out
      And I am on the "Quiz 1" "quiz_answersheets > Report" page logged in as "teacher"
      When I click on "Review sheet" "link" in the "Student One" "table_row"
      Then I should not see "No recording"
      And "Response recorded: recorder1.ogg" "text" in the "First question" "question" should not be visible
