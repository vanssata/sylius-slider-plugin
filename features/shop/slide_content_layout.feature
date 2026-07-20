@slider_frontend
Feature: Rendering edge-to-edge slide content
    In order to present full-width banner strips
    As a Visitor
    I want slide content to span edge to edge with a height cap

    Background:
        Given slider demo fixtures are loaded

    Scenario: Full-width bottom strip capped at 30% of the slide height
        Given the slide "new-collection" has responsive "contentWidth" set to "full" for "desktop"
        And the slide "new-collection" has responsive "contentVerticalPosition" set to "bottom" for "desktop"
        And the slide "new-collection" has responsive "contentMaxHeight" set to "30%" for "desktop"
        When I visit the slider page for code "fashion-classic-arrows"
        Then I should see the storefront slider component
        And the slide "new-collection" content style should contain "--vanssa-slide-content-width: 100%"
        And the slide "new-collection" content style should contain "--vanssa-slide-content-gutter: 0px"
        And the slide "new-collection" content style should contain "--vanssa-slide-content-max-height: 30%"
        And the slide "new-collection" content style should contain "--vanssa-slide-content-shift-y: 0px"

    Scenario: Boxed content keeps the default reading width
        When I visit the slider page for code "fashion-classic-arrows"
        Then I should see the storefront slider component

    @javascript
    Scenario: Tablet overrides apply at tablet viewport and cascade to mobile
        Given the slide "new-collection" has responsive "headlineColor" set to "rgb(255, 34, 0)" for "tablet"
        When I visit the slider page for code "fashion-classic-arrows"
        Then the slide "new-collection" headline color should not be "rgb(255, 34, 0)" at viewport 1400x900
        And the slide "new-collection" headline color should be "rgb(255, 34, 0)" at viewport 820x1180
        And the slide "new-collection" headline color should be "rgb(255, 34, 0)" at viewport 390x844
