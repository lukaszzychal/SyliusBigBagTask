@shop @ui @admin_notes_not_visible
Feature: Admin notes are not visible in shop
  In order to protect internal information
  As a customer (logged in or guest)
  I should not be able to see admin notes in the shop

  Background:
    Given the store operates on a single channel
    Given there is a customer account "customer@example.com"
    And the store has a product "Test Product" with code "TEST-PRODUCT"
    And test order "000001" exists for "customer@example.com" with product "TEST-PRODUCT"
    And the order "#000001" has admin notes "Secret admin note"

  Scenario: Guest customer cannot see admin notes on order summary page
    When I am on the order "#000001" summary page with token
    Then I should not see "Secret admin note"
    And I should not see "admin notes" text

  Scenario: Logged in customer cannot see admin notes on their order page
    Given I am logged in as "customer@example.com"
    When I am on my order "#000001" page
    Then I should not see "Secret admin note"
    And I should not see "admin notes" text

  Scenario: Admin notes field is not present in shop order forms
    When I am on the order "#000001" summary page with token
    Then the form should not contain "adminNotes" field

