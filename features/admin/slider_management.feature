@slider_admin
Feature: Managing sliders in admin
    In order to prepare fashion campaign pages
    As an administrator
    I want to see and edit demo sliders

    Scenario: Seeing generated sliders on admin index
        Given slider demo fixtures are loaded
        When I am logged in to the administration as "sylius"
        And I go to the slider index page
        Then I should see slider entry "Fashion Classic Arrows"
        And I should see slider entry "Fashion Minimal Fade"
        And I should see slider entry "Fashion Autoplay Showcase"

    Scenario: Seeing slide preview list on slider update
        Given slider demo fixtures are loaded
        When I am logged in to the administration as "sylius"
        And I go to the slider update page for code "fashion-fullscreen-hero"
        Then I should see slide code "new-collection" in slider preview
        And I should see slide code "runway-video" in slider preview
