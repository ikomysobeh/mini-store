# Requirements Document

## Introduction

This specification addresses two critical user-facing issues in the e-commerce application:
1. The website title displayed next to the logo needs to be removed or replaced with the actual site name from settings
2. Language switching functionality causes 404 errors on subsequent attempts after the first switch

## Glossary

- **Site Name**: The configurable name of the website stored in the settings table
- **Logo Component**: The Vue component that displays the website logo and branding
- **Language Switcher**: The UI component that allows users to switch between Arabic and English
- **Locale Prefix**: The language code (en/ar) that appears in the URL path
- **Default Locale**: The primary language of the site (Arabic in this case)
- **hideDefaultLocaleInURL**: Laravel Localization configuration that hides the default locale prefix from URLs

## Requirements

### Requirement 1

**User Story:** As a website visitor, I want to see properly styled site branding next to the logo, so that the website looks professional on all devices

#### Acceptance Criteria

1. WHEN viewing the logo component THEN the system SHALL display the site name with proper flex layout
2. WHEN viewing on mobile devices THEN the system SHALL adjust the text size appropriately for smaller screens
3. WHEN the logo and text render together THEN the system SHALL maintain proper spacing and alignment using flexbox
4. WHEN the site name is long THEN the system SHALL handle text overflow gracefully without breaking the layout

### Requirement 2

**User Story:** As a website visitor, I want to switch between Arabic and English languages reliably, so that I can view the site in my preferred language without encountering errors

#### Acceptance Criteria

1. WHEN a user switches from Arabic to English THEN the system SHALL navigate to the correct English URL without including "/public/" in the path
2. WHEN a user switches from English to Arabic THEN the system SHALL navigate to the correct Arabic URL without including "/public/" in the path
3. WHEN building the new language URL THEN the system SHALL remove any existing locale prefix before adding the new one
4. WHEN building the new language URL THEN the system SHALL remove any "/public/" segments from the path
5. WHEN the default locale (Arabic) is active THEN the system SHALL generate URLs without the locale prefix
6. WHEN a non-default locale (English) is active THEN the system SHALL include the locale prefix in the URL
