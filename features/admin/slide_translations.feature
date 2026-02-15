@slider_admin
Feature: Slide translations in admin
    In order to translate slides to multiple locales
    As an administrator
    I want to see all locale accordions on the slide form

    Scenario: Seeing multiple translation locale accordions on slide create form
        Given the locale "en_US" exists
        And the locale "de_DE" exists
        When I am logged in to the administration as "sylius"
        And I go to the slide creation page
        Then I should see translation accordion for locale "en_US"
        And I should see translation accordion for locale "de_DE"
