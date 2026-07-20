@slider_admin @javascript
Feature: Editing slides in the two-column workspace
    In order to configure slides comfortably while watching the result
    As an Administrator
    I want a live preview with a toolbar-driven settings drawer

    Background:
        Given slide demo fixtures are loaded
        And there is an administrator "behat-admin" identified by "behat-password"
        And I sign in to the administration as "behat-admin" with password "behat-password"
        And I go to the slide update page for code "new-collection"

    Scenario: Opening the settings drawer with always-visible controls
        Then the settings drawer should be closed
        When I open the settings drawer
        Then the drawer head should offer save, language and breakpoint controls
        And the drawer body should scroll independently

    Scenario: The toolbar drives which breakpoint sections are edited
        When I open the settings drawer
        And I switch the toolbar breakpoint to "tablet"
        Then only the "tablet" form sections should be visible
        When I switch the toolbar breakpoint to "desktop"
        Then only the "desktop" form sections should be visible

    Scenario: The toolbar language switch swaps base fields for the translation
        When I open the settings drawer
        And I switch the toolbar language to "en_US"
        Then the base media card should be hidden

    Scenario: Editing a field live-refreshes the preview
        When I open the settings drawer
        And I switch the toolbar language to "en_US"
        And I fill the "en_US" desktop title with "Live draft QA"
        Then I wait until the preview frame contains "Live draft QA"

    Scenario: Clicking a style preset fills the mapped fields
        When I open the settings drawer
        And I hover the style preset "hero_dark"
        And I click the style preset "hero_dark"
        Then the form field "slide[settings][responsive][desktop][textColor]" should have value "rgba(255, 255, 255, 1)"

    Scenario: Fullscreen workspace toggles and exits with Escape
        When I toggle the fullscreen workspace
        Then the workspace should be fullscreen
        When I press the escape key
        Then the workspace should not be fullscreen

    Scenario: The preview is scaled to fit its panel
        When I open the settings drawer
        And I switch the toolbar breakpoint to "mobile"
        Then the preview should be scaled to fit
