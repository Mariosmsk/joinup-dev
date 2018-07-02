@api @tallinn
Feature:
  As a moderator of Joinup
  I want to be able to be aware when was the last update time of a tallinn report.

  Background:
    Given users:
      | Username | Roles     |
      | karma    | moderator |

  Scenario: Tallinn reports that have never been updated should not show last update date.
    # Timestamp 1530529671 translates to 02-07-2018.
    Given tallinn_report content:
      | title          | collection                      | created    | changed    |
      | Romania Report | Tallinn Ministerial Declaration | 1530529671 | 1530529671 |
    When I am logged in as "karma"
    And I go to the "Tallinn Ministerial Declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the text "Last update: -" in the "Romania Report" tile
    And I should not see the text "02/07/2018" in the "Romania Report" tile

  Scenario: Tallinn reports that have been updated should show last update date.
    # Timestamp 1530443270 translates to 01-07-2018, timestamp 1530529671 translates to 02-07-2018.
    Given tallinn_report content:
      | title          | collection                      | created    | changed    |
      | Romania Report | Tallinn Ministerial Declaration | 1530443270 | 1530529671 |
    When I am logged in as "karma"
    And I go to the "Tallinn Ministerial Declaration" collection
    And I click "Tallinn initiative" in the "Left sidebar" region
    Then I should see the text "Last update: 02/07/2018" in the "Romania Report" tile
