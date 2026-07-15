@slider_admin
Feature: Configuring slider presentation options in admin
    In order to fine-tune how sliders behave on the storefront
    As an administrator
    I want to manage arrows, pagination, navigation and media options

    Scenario: Seeing the new presentation option fields on slider update
        Given slider demo fixtures are loaded
        When I am logged in to the administration as "sylius"
        And I go to the slider update page for code "homepage-main"
        Then the slider settings field "arrowsPosition" should be present
        And the slider settings field "arrowsVerticalAlign" should be present
        And the slider settings field "paginationPosition" should be present
        And the slider settings field "paginationStyle" should be present
        And the slider settings field "keyboardNavigation" should be present
        And the slider settings field "touchSwipe" should be present
        And the slider settings field "showProgressBar" should be present
        And the slider settings field "lazyLoadMedia" should be present

    Scenario: Seeing persisted presentation options selected on slider update
        Given slider demo fixtures are loaded
        And the slider "homepage-main" is configured with "arrowsPosition" set to "outside"
        And the slider "homepage-main" is configured with "paginationStyle" set to "numbers"
        When I am logged in to the administration as "sylius"
        And I go to the slider update page for code "homepage-main"
        Then the slider settings field "arrowsPosition" should have selected value "outside"
        And the slider settings field "paginationStyle" should have selected value "numbers"

    Scenario: Seeing the channel and language preview panel on slider update
        Given slider demo fixtures are loaded
        When I am logged in to the administration as "sylius"
        And I go to the slider update page for code "homepage-main"
        Then I should see the slider preview panel
