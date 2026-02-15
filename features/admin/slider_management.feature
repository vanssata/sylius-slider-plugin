@slider_admin
Feature: Managing sliders in admin
    In order to prepare automotive campaign pages
    As an administrator
    I want to see and edit demo sliders

    Scenario: Seeing generated sliders on admin index
        Given slider demo fixtures are loaded
        When I am logged in to the administration as "sylius"
        And I go to the slider index page
        Then I should see slider entry "Homepage Main Slider"
        And I should see slider entry "Fleet Suite Slider"
        And I should see slider entry "Service Operations Slider"

    Scenario: Seeing slide preview list on slider update
        Given slider demo fixtures are loaded
        When I am logged in to the administration as "sylius"
        And I go to the slider update page for code "homepage-main"
        Then I should see slide code "platform-overview" in slider preview
        And I should see slide code "autonomous-loop" in slider preview
