@api @group-a
Feature: Asset distribution editing.
  As a privileged user of the website
  I want to track downloads of distributions
  So I can create some metrics

  @javascript
  Scenario: Downloads made by authenticated users are logged.
    Given the following licence:
      | title       | Postcard licence                     |
      | description | Send a postcard from where you live. |
      | type        | Attribution                          |
    And collection:
      | title | Berkeley Software Distributions |
      | state | validated                       |
    And solution:
      | title      | OpenBSD                         |
      | collection | Berkeley Software Distributions |
      | state      | validated                       |
    And release:
      | title          | Winter of 95 |
      | release number | 6.1          |
      | is version of  | OpenBSD      |
      | state          | validated    |
    And distributions:
      | title          | description                 | parent       | access url                                                  | licence          |
      | i386           | The i386 version            | Winter of 95 | https://mirrors.evowise.com/pub/OpenBSD/6.1/i386/random.iso | Postcard licence |
      | Changelog      | Detailed Changelog          | Winter of 95 | text.pdf                                                    | Postcard licence |
      | OpenBSD images | Images for logos and flyers | OpenBSD      | test.zip                                                    | Postcard licence |
    And users:
      | Username           | E-mail                        |
      | Bradley Emmett     | bradley.emmett@example.com    |
      | Marianne Sherburne | marianne.herburne@example.com |

    When I am logged in as "Bradley Emmett"
    And I go to the "Berkeley Software Distributions" collection
    And I click "OpenBSD"
    And I click "Download releases"
    Then I should see "Releases for OpenBSD solution"

    And I should see the download link in the "OpenBSD images" asset distribution
    And I should see the download link in the "Changelog" asset distribution
    And I should see the external link in the "i386" asset distribution

    # Clicking these links will track the download event.
    Then I click "Download" in the "OpenBSD images" asset distribution
    And I click "Download" in the "Changelog" asset distribution

    When I am an anonymous user
    And I go to the "OpenBSD" solution
    And I click "Download releases"
    Then I should see "Releases for OpenBSD solution"

    # The same download links are shown to anonymous users.
    And I should see the download link in the "OpenBSD images" asset distribution
    And I should see the download link in the "Changelog" asset distribution
    And I should see the external link in the "i386" asset distribution

    # Anonymous users will be prompted with a modal to enter their e-mails.
    When I click "Download" in the "OpenBSD images" asset distribution
    Then a modal should open
    And I should see the text "Download in progress"
    And I should see the text "If you do not have a Joinup account, please take some time to create one, at this page. It will allow you to fully exploit the functionalities of Joinup to create new content, contribute to existing one and collaborate with other users."
    And I should see the text "If you do not want to create a Joinup account, please select any of the following options and provide your email address if you want to be kept updated on the status of the solution. Your personal data will be treated in compliance with the legal notice"
    When I fill in "E-mail address" with "trackme@example.com" in the "Modal content" region
    Then I press "Submit" in the "Modal buttons" region
    Then the modal should be closed

    # Verify that users can opt-out from inserting their e-mail.
    When I click "Download" in the "Changelog" asset distribution
    Then a modal should open
    When I press "No thanks" in the "Modal buttons" region
    Then the modal should be closed

    # Verify that the modal also shows in the overview page.
    When I click "Changelog" in the "Changelog" asset distribution
    Then I should see the link "Download"
    When I click "Download"
    Then a modal should open
    When I press "No thanks" in the "Modal buttons" region
    Then the modal should be closed

    When I am logged in as a user with the moderator role
    And I go to the distribution downloads page
    Then I should see the following download entries:
      | user                     | e-mail                     | distribution   |
      | Anonymous (not verified) | trackme@example.com        | OpenBSD images |
      | Bradley Emmett           | bradley.emmett@example.com | Changelog      |
      | Bradley Emmett           | bradley.emmett@example.com | OpenBSD images |

    # Verify that the tracking happens when clicking the Download button in
    # the tile view mode and in the canonical page view of the distribution.
    When I am logged in as "Marianne Sherburne"
    And I go to the "OpenBSD" solution
    # The only download link is the one in the "OpenBSD images" tile.
    Then I click "Download"
    And I click "Winter of 95"
    # The only download link is the one in the "Changelog" tile.
    And I click "Download"

    When I am logged in as a user with the moderator role
    And I go to the distribution downloads page
    Then I should see the following download entries:
      | user                     | e-mail                        | distribution   |
      | Marianne Sherburne       | marianne.herburne@example.com | Changelog      |
      | Marianne Sherburne       | marianne.herburne@example.com | OpenBSD images |
      | Anonymous (not verified) | trackme@example.com           | OpenBSD images |

  Scenario: Tests the CSV download.
    Given users:
      | Username | E-mail            |
      | user1    | user1@example.com |
      | user2    | user2@example.com |
    And the following solution:
      | title | Solution  |
      | state | validated |
    And the following distributions:
      | title          | parent   | access url |
      | Distribution 1 | Solution | text.pdf   |
      | Distribution 2 | Solution | test.zip   |
      | Distribution 3 | Solution | test1.zip  |
    And the following distribution download events:
      | distribution   | user                |
      | Distribution 1 | visitor@example.com |
      | Distribution 1 | user1               |
      | Distribution 2 | user2               |
      | Distribution 3 | anon@example.com    |
      | Distribution 3 | user1               |

    Given I am logged in as a moderator
    And I go to the distribution downloads page

    When I click "Download CSV"
    And I wait for the batch process to finish
    Then I should see the success message "Export complete. Download the file here if file is not automatically downloaded."
    And I should see the link "here"
    And the file downloaded from the "here" link contains the following strings:
      | ID,User,Email,"File name",Distribution,Created                             |
      | ,"Anonymous (not verified)",visitor@example.com,text.pdf,"Distribution 1", |
      | ,user1,user1@example.com,text.pdf,"Distribution 1",                        |
      | ,user2,user2@example.com,test.zip,"Distribution 2",                        |
      | ,"Anonymous (not verified)",anon@example.com,test1.zip,"Distribution 3",   |
      | ,user1,user1@example.com,test1.zip,"Distribution 3",                       |