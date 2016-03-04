@api
Feature: Collection Page API
  In order to manage collection pages programmatically
  As a backend developer
  I need to be able to use the Node API to handle the "Collection Page" bundle

  Scenario: Programmatically create a Collection Page
    Given the following collection:
      | name            | Open Data Initiative    |
      | author          | Mightily Oats           |
      | logo            | logo.png                |
      | pre-moderation  | 0                       |
      | closed          | 0                       |
      | create elibrary | facilitators            |
      | schedule        | daily                   |
      | metadata url    | http://joinup.eu/my/foo |
      | uri             | http://joinup.eu/my/foo |
    And collection_page content:
      | title              | body                                     | groups audience         |
      | Dummy Page         | This is some dummy content like foo:bar. | http://joinup.eu/my/foo |
     # @Fixme unimplemented. See ISAICP-2369
     # | Exclude from menu |                                              |
     Then I should have a "Collection Page" page titled "Dummy Page"
