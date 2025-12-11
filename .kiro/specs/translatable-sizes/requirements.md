# Requirements Document

## Introduction

This feature adds multilingual support to the Size model, allowing administrators to provide Arabic and English translations for size names. Since sizes can be custom values created by administrators (e.g., "For adults", "For children under 8 years old", "One Size"), they require database-level translation support similar to the existing product translation system.

## Glossary

- **Size**: A product attribute representing dimensions or fit categories (e.g., "Small", "One Size", "For adults")
- **Size System**: The Laravel Eloquent model and database table that stores size information
- **TranslatableProductTrait**: An existing trait that provides locale-aware attribute accessors for name_en/name_ar fields
- **Admin Interface**: The administrative panel where users manage sizes
- **Locale**: The language setting (either 'en' for English or 'ar' for Arabic)

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to add sizes with both English and Arabic names, so that customers see sizes in their preferred language.

#### Acceptance Criteria

1. WHEN an administrator creates a new size THEN the Size System SHALL store both name_en and name_ar values
2. WHEN an administrator views the size creation form THEN the Size System SHALL display input fields for both English and Arabic names
3. WHEN an administrator submits a size with only one language filled THEN the Size System SHALL accept the submission and store the provided translation
4. WHEN the name attribute is accessed THEN the Size System SHALL return name_ar if the current locale is 'ar' and name_ar is not empty, otherwise return name_en

### Requirement 2

**User Story:** As an administrator, I want to edit existing sizes in both languages, so that I can update or correct translations.

#### Acceptance Criteria

1. WHEN an administrator opens the size edit form THEN the Size System SHALL display current values for both name_en and name_ar fields
2. WHEN an administrator updates either name_en or name_ar THEN the Size System SHALL save the changes to the database
3. WHEN an administrator updates a size THEN the Size System SHALL preserve any existing translation values that were not modified

### Requirement 3

**User Story:** As a customer, I want to see product sizes in my selected language, so that I can understand the available options.

#### Acceptance Criteria

1. WHEN a customer views a product with the Arabic locale selected THEN the Size System SHALL display size names in Arabic
2. WHEN a customer views a product with the English locale selected THEN the Size System SHALL display size names in English
3. WHEN a size has no translation for the current locale THEN the Size System SHALL display the available translation as a fallback
4. WHEN sizes are displayed in a list THEN the Size System SHALL maintain the sort_order regardless of locale

### Requirement 4

**User Story:** As a developer, I want to migrate existing size data to the new structure, so that current sizes continue to work without data loss.

#### Acceptance Criteria

1. WHEN the migration runs THEN the Size System SHALL add name_en and name_ar columns to the sizes table
2. WHEN the migration runs THEN the Size System SHALL copy existing name values to the name_en column
3. WHEN the migration runs THEN the Size System SHALL preserve all existing size records without data loss
4. WHEN the migration runs THEN the Size System SHALL remove the old name column after data migration is complete
