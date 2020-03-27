@api
Feature:
  As the owner of the website
  In order for my site to be more appealing to the search engines
  I need to have basic metatags attached to the page.

  Scenario Outline: Front page metatags.
    Given I am <user type>
    When I am on the homepage
    Then the following meta tags should available in the html:
      | identifier          | value                                                                                                                                                                                                                      |
      | description         | Joinup is a collaborative platform created by the European Commission and funded by the European Union via the Interoperability solutions for public administrations, businesses and citizens (ISA2) Programme.            |
      | abstract            | Joinup offers several services that aim to help e-Government professionals share their experience with each other. We also hope to support them to find, choose, re-use, develop and implement interoperability solutions. |
      | og:url              | $base_url$/                                                                                                                                                                                                                |
      | og:site_name        | Joinup                                                                                                                                                                                                                     |
      | og:title            | Joinup                                                                                                                                                                                                                     |
      | og:image            | https://joinup.ec.europa.eu/themes/joinup/images/logo.svg                                                                                                                                                                  |
      | og:image:secure_url | https://joinup.ec.europa.eu/themes/joinup/images/logo.svg                                                                                                                                                                  |
      | og:image:type       | image/svg                                                                                                                                                                                                                  |
    And the HTML title of the page should be "Joinup"

    When I click "Collections"
    Then the following meta tags should available in the html:
      | identifier          | value                                                                                                                                                                                                                      |
      | og:url              | $base_url$/collections                                                                                                                                                                                                     |
      | og:site_name        | Joinup                                                                                                                                                                                                                     |
      | og:title            | Collections                                                                                                                                                                                                                |
      | og:image            | https://joinup.ec.europa.eu/themes/joinup/images/logo.svg                                                                                                                                                                  |
      | og:image:secure_url | https://joinup.ec.europa.eu/themes/joinup/images/logo.svg                                                                                                                                                                  |
      | og:image:type       | image/svg                                                                                                                                                                                                                  |

    Examples:
      | user type                                         |
      | an anonymous user                                 |
      | logged in as a user with the "authenticated" role |
