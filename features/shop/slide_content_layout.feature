@slider_frontend
Feature: Rendering edge-to-edge slide content
    In order to present full-width banner strips
    As a Visitor
    I want slide content to span edge to edge with a height cap

    Background:
        Given slider demo fixtures are loaded

    Scenario: Full-width bottom strip capped at 30% of the slide height
        Given the slide "platform-overview" has responsive "contentWidth" set to "full" for "desktop"
        And the slide "platform-overview" has responsive "contentVerticalPosition" set to "bottom" for "desktop"
        And the slide "platform-overview" has responsive "contentMaxHeight" set to "30%" for "desktop"
        When I visit the slider page for code "homepage-main"
        Then I should see the storefront slider component
        And the slide "platform-overview" content style should contain "--vanssa-slide-content-width: 100%"
        And the slide "platform-overview" content style should contain "--vanssa-slide-content-gutter: 0px"
        And the slide "platform-overview" content style should contain "--vanssa-slide-content-max-height: 30%"
        And the slide "platform-overview" content style should contain "--vanssa-slide-content-shift-y: 0px"

    Scenario: Boxed content keeps the default reading width
        When I visit the slider page for code "homepage-main"
        Then I should see the storefront slider component
