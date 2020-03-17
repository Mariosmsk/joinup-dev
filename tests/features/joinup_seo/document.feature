@api @group-a
Feature: SEO for document content.
  As an owner of the website
  in order for my documents to be better visible on the web
  I need proper metatag to be encapsulated in the html code.

  Scenario: Basic metatags are attached as JSON schema on the page.
    Given collections:
      | title                          | state     |
      | Joinup SEO document collection | validated |
    And licence:
      | uri   | https://example.com/license1 |
      | title | Some license                 |
      | type  | Public domain                |
    And users:
      | Username          | E-mail                 | First name | Family name |
      | Joinup SEO author | joinup.seo@example.com | Scrapper   | Jedi        |
    And document content:
      | title        | author            | document type | document publication date       | changed                         | keywords         | short title | file type | file     | body               | licence      | state     | collection                     |
      | SEO document | Joinup SEO author | document      | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 01 Jan 2020 13:00:00 +0100 | key1, key2, key3 | SEO         | upload    | test.zip | Document test1.zip | Some license | validated | Joinup SEO document collection |

    When I visit the "SEO document" document
    Then the metatag JSON should be attached in the page
    And 1 metatag graph of type "DigitalDocument" should exist in the page
    And the metatag graph of the item with "name" "SEO document" should have the following properties:
      | property            | value                                                |
      | @type               | DigitalDocument                                      |
      | headline            | SEO document                                         |
      | name                | SEO document                                         |
      | license             | https://example.com/license1                         |
      | description         | Document test1.zip                                   |
      | datePublished       | 2019-12-25T14:00:00+0100                             |
      | isAccessibleForFree | True                                                 |
      | dateModified        | 2020-01-01T13:00:00+0100                             |
      | mainEntityOfPage    | $base_url$/sites/default/files/test$random_text$.zip |
    # Adding numerical property values is turning the "about" property into an array comparison.
    And the metatag graph of the item with "name" "SEO document" should have the following "about" properties:
      | property | value |
      | 0        | key1  |
      | 1        | key2  |
      | 2        | key3  |
    And the metatag graph of the item with "name" "SEO document" should have the following "associatedMedia" properties:
      | property      | value                                                |
      | @type         | MediaObject                                          |
      # $random_text$ can be any string that is appointed by the system and we
      # cannot predict. In this case it is the random file name suffix before the file extension.
      | @id           | $base_url$/sites/default/files/test$random_text$.zip |
      | name          | test.zip                                             |
      | url           | $base_url$/sites/default/files/test$random_text$.zip |
      | datePublished | 2019-12-25T14:00:00+0100                             |
    And the metatag graph of the item with "name" "SEO document" should have the following "author" properties:
      | property | value                         |
      | @type    | Person                        |
      # The user id is only a number but we can be quite certain that this will be a url to the user since the
      # $random_text$ does not include a / character.
      | @id      | $base_url$/user/$random_text$ |
      | name     | Scrapper Jedi                 |
      | url      | $base_url$/user/$random_text$ |

    When I click "Keep up to date"
    Then I should see the "SEO document" tile
    # No metatags are defined for the keep up to date page.
    # No metatags JSON in general means also that the entity metatags of the
    # news item is also not attached when the tile is present.
    And the metatag JSON should not be attached in the page
