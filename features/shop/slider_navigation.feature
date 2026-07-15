@slider_frontend
Feature: Configuring slider navigation presentation
    In order to adapt the slider to different page designs
    As a store owner
    I want arrows, pagination and media loading to follow the configured options

    Scenario: Rendering numbered pagination and outside arrows
        Given slider demo fixtures are loaded
        And slider "homepage-main" has setting "paginationStyle" set to "numbers"
        And slider "homepage-main" has setting "paginationPosition" set to "bottom-outside"
        And slider "homepage-main" has setting "arrowsPosition" set to "outside"
        When I visit the slider page for code "homepage-main"
        Then I should see the storefront slider component
        And I should see slider with css class "vanssa-slider--arrows-outside"
        And I should see slider with css class "vanssa-slider--pagination-bottom-outside"
        And slider stimulus options should include "paginationStyle" with value "numbers"

    Scenario: Exposing keyboard and swipe navigation to the storefront controller
        Given slider demo fixtures are loaded
        And slider "homepage-main" has setting "keyboardNavigation" set to "true"
        And slider "homepage-main" has setting "touchSwipe" set to "true"
        And slider "homepage-main" has setting "lazyLoadMedia" set to "true"
        When I visit the slider page for code "homepage-main"
        Then slider stimulus options should include "keyboardNavigation" with value "true"
        And slider stimulus options should include "touchSwipe" with value "true"
        And I should see a lazy loaded slide image

    Scenario: Disabling keyboard navigation
        Given slider demo fixtures are loaded
        And slider "homepage-main" has setting "keyboardNavigation" set to "false"
        When I visit the slider page for code "homepage-main"
        Then slider stimulus options should include "keyboardNavigation" with value "false"

    Scenario: Showing the autoplay progress bar
        Given slider demo fixtures are loaded
        And slider "homepage-main" has setting "showProgressBar" set to "true"
        And slider "homepage-main" has autoplay enabled
        When I visit the slider page for code "homepage-main"
        Then I should see the storefront slider component
        And I should see the autoplay progress bar
