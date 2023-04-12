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

  @javascript
  Scenario: Create a record audio and video question
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I add a "item_qtype_recordrtc" question filling the form with:
      | Question name                  | Record audio question                               |
      | Question text                  | <p>Please record yourself talking about Moodle.</p> |
      | General feedback               | <p>I hope you spoke clearly and coherently.</p>     |
      | id_defaultmark                 | 2                                                   |
      | id_mediatype                   | video                                               |
      | id_timelimitinseconds_number   | 15                                                  |
      | id_timelimitinseconds_timeunit | 1                                                   |
      | Allow pausing                  | Yes                                                 |
      | Students can self-rate         | Yes                                                 |
      | Students can self-comment      | Yes                                                 |
    Then "Record audio question" "table_row" should exist

    # Check that the next new question form displays user preferences settings.
    And I press "Create a new question ..."
    And I set the field "item_qtype_recordrtc" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And the following fields match these values:
      | id_defaultmark                 | 2     |
      | id_mediatype                   | video |
      | id_timelimitinseconds_number   | 15    |
      | id_timelimitinseconds_timeunit | 1     |
      | Allow pausing                  | Yes   |
      | Students can self-rate         | Yes   |
      | Students can self-comment      | Yes   |

  @javascript
  Scenario: Validation and reload form workflow
    Given I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "item_qtype_recordrtc" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    When I set the following fields to these values:
      | Question name     | AV question                            |
      | Type of recording | Multiple widgets                       |
      | Question text     | What is your name? [[your name:audio]] |

    And I press "Update the form"
    Then I should see "‘your name’ is not a valid name. Names must be lower-case letters, without spaces. This has been fixed for you."
    And I should see "The placeholder format is"
    And I should not see "The form was updated. You can add per-input feedback below."

    And I press "Update the form"
    And I should not see "The placeholder format is"
    And I should see "The form was updated. You can add per-input feedback below."

    And I set the following fields to these values:
      | Allow pausing            | Yes                                         |
      | Feedback for 'your_name' | Well, that is hopefully something you know. |

    And I press "id_submitbutton"
    And "AV question" "table_row" should exist
