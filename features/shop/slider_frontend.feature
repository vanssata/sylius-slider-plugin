@slider_frontend
Feature: Rendering slider on storefront
    In order to present automotive software content to customers
    As a store owner
    I want sliders with images and video placeholders to render on the shop page

    Scenario: Viewing the homepage main slider
        Given slider demo fixtures are loaded
        When I visit the slider page for code "homepage-main"
        Then I should see the storefront slider component
        And I should see slider text "Platform Overview"

    Scenario: Viewing slider with parallax strength configured
        Given slider demo fixtures are loaded
        And slider "homepage-main" has parallax strength "2rem"
        When I visit the slider page for code "homepage-main"
        Then I should see the storefront slider component
        And I should see slider with css class "vanssa-slider--parallax"
        And slider stimulus options should include parallax strength "2rem"
