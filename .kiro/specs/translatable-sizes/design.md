# Design Document

## Overview

This feature adds multilingual support to the Size model by implementing a database-level translation system using separate `name_en` and `name_ar` columns. The design follows the existing translation pattern used by the Product and Category models, utilizing the `TranslatableProductTrait` to provide locale-aware attribute accessors.

## Architecture

The solution consists of three main components:

1. **Database Layer**: Migration to add translation columns and migrate existing data
2. **Model Layer**: Update the Size model to use the translatable trait
3. **Admin Interface Layer**: Update size management forms to support both languages

The architecture maintains consistency with existing translatable models (Product, Category) to ensure a uniform approach across the application.

## Components and Interfaces

### 1. Database Schema

**Modified Table: `sizes`**

Columns to add:
- `name_en` (string, nullable): English name for the size
- `name_ar` (string, nullable): Arabic name for the size

Columns to remove:
- `name` (string): Legacy single-language name column

The migration will:
1. Add new columns
2. Copy existing `name` values to `name_en`
3. Drop the old `name` column

### 2. Size Model

**File**: `app/Models/Size.php`

Changes:
- Add `use TranslatableProductTrait` to enable locale-aware accessors
- Update `$fillable` array to include `name_en` and `name_ar` instead of `name`
- Add `$appends = ['name']` to ensure the computed `name` attribute is included in JSON responses
- The trait will automatically provide `getNameAttribute()` method that returns the appropriate translation based on current locale

### 3. Admin Forms

Size creation and edit forms need to be updated to include fields for both languages:

**Form Fields**:
- English Name input field (name_en)
- Arabic Name input field (name_ar)
- Existing fields: category_type, is_active, sort_order

**Validation Rules**:
- At least one name (name_en or name_ar) must be provided
- Both fields are optional individually, but not both can be empty

## Data Models

### Size Model Structure

```php
class Size extends Model
{
    use HasFactory, TranslatableProductTrait;

    protected $fillable = [
        'name_en',
        'name_ar',
        'category_type',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'name',
    ];

    // ... existing relationships and scopes remain unchanged
}
```

### Translation Behavior

The `TranslatableProductTrait` provides:
- `getNameAttribute()`: Returns `name_ar` if locale is 'ar' and value exists, otherwise returns `name_en`
- Fallback logic: If the preferred locale's translation is empty, falls back to the other language

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Acceptance Criteria Testing Prework

1.1 WHEN an administrator creates a new size THEN the Size System SHALL store both name_en and name_ar values
  Thoughts: This is about ensuring that when we create a size with both translations, both values are persisted to the database. We can test this by generating random size data with both translations, creating the size, then retrieving it and verifying both fields match what was submitted.
  Testable: yes - property

1.2 WHEN an administrator views the size creation form THEN the Size System SHALL display input fields for both English and Arabic names
  Thoughts: This is a UI requirement about what fields should be present in a form. This is not a backend logic property we can test with property-based testing.
  Testable: no

1.3 WHEN an administrator submits a size with only one language filled THEN the Size System SHALL accept the submission and store the provided translation
  Thoughts: This tests that partial translations are valid. We can generate sizes with only name_en or only name_ar and verify they save successfully.
  Testable: yes - property

1.4 WHEN the name attribute is accessed THEN the Size System SHALL return name_ar if the current locale is 'ar' and name_ar is not empty, otherwise return name_en
  Thoughts: This is testing the locale-aware accessor behavior. We can test this by creating sizes with various translation combinations, setting different locales, and verifying the correct translation is returned.
  Testable: yes - property

2.1 WHEN an administrator opens the size edit form THEN the Size System SHALL display current values for both name_en and name_ar fields
  Thoughts: This is a UI requirement about form population. Not testable with property-based testing.
  Testable: no

2.2 WHEN an administrator updates either name_en or name_ar THEN the Size System SHALL save the changes to the database
  Thoughts: This tests that updates to translation fields persist correctly. We can generate random sizes, update one or both translation fields, and verify the changes are saved.
  Testable: yes - property

2.3 WHEN an administrator updates a size THEN the Size System SHALL preserve any existing translation values that were not modified
  Thoughts: This tests that partial updates don't overwrite other fields. We can create a size with both translations, update only one field, and verify the other remains unchanged.
  Testable: yes - property

3.1 WHEN a customer views a product with the Arabic locale selected THEN the Size System SHALL display size names in Arabic
  Thoughts: This is testing locale-specific display behavior. We can set locale to 'ar', access the name attribute, and verify it returns name_ar when available.
  Testable: yes - property

3.2 WHEN a customer views a product with the English locale selected THEN the Size System SHALL display size names in English
  Thoughts: This is testing locale-specific display behavior. We can set locale to 'en', access the name attribute, and verify it returns name_en.
  Testable: yes - property

3.3 WHEN a size has no translation for the current locale THEN the Size System SHALL display the available translation as a fallback
  Thoughts: This tests fallback behavior. We can create sizes with only one translation, set locale to the missing translation's language, and verify it falls back to the available translation.
  Testable: yes - property

3.4 WHEN sizes are displayed in a list THEN the Size System SHALL maintain the sort_order regardless of locale
  Thoughts: This tests that sorting behavior is consistent across locales. We can create multiple sizes with different sort_orders, query them in different locales, and verify the order remains consistent.
  Testable: yes - property

4.1 WHEN the migration runs THEN the Size System SHALL add name_en and name_ar columns to the sizes table
  Thoughts: This is about database schema changes during migration. This is a one-time operation that's better tested manually or with integration tests.
  Testable: no

4.2 WHEN the migration runs THEN the Size System SHALL copy existing name values to the name_en column
  Thoughts: This is about data migration correctness. This is a one-time operation better tested with integration tests or manual verification.
  Testable: no

4.3 WHEN the migration runs THEN the Size System SHALL preserve all existing size records without data loss
  Thoughts: This is about migration safety. This is a one-time operation better tested with integration tests.
  Testable: no

4.4 WHEN the migration runs THEN the Size System SHALL remove the old name column after data migration is complete
  Thoughts: This is about schema cleanup during migration. This is a one-time operation.
  Testable: no

### Property Reflection

After reviewing all testable properties, I've identified the following consolidations:

- Properties 3.1 and 3.2 can be combined into a single property that tests locale-aware name retrieval for both locales
- Properties 1.1 and 2.2 both test persistence of translation fields and can be combined
- Property 2.3 is unique and tests partial update behavior
- Property 1.3 tests that partial translations are valid (edge case of property 1.1)
- Property 1.4 is redundant with the combined 3.1/3.2 property
- Property 3.3 tests fallback behavior (unique)
- Property 3.4 tests sort order consistency (unique)

Consolidated properties:
1. Translation persistence (combines 1.1, 1.3, 2.2)
2. Locale-aware name retrieval (combines 1.4, 3.1, 3.2)
3. Partial update preservation (2.3)
4. Fallback behavior (3.3)
5. Sort order consistency (3.4)

### Correctness Properties

Property 1: Translation persistence
*For any* size with name_en and/or name_ar values, when the size is created or updated, retrieving the size from the database should return the same translation values that were stored.
**Validates: Requirements 1.1, 1.3, 2.2**

Property 2: Locale-aware name retrieval
*For any* size with translations, when the name attribute is accessed with locale set to 'ar', it should return name_ar if not empty, otherwise name_en; when locale is 'en', it should return name_en if not empty, otherwise name_ar.
**Validates: Requirements 1.4, 3.1, 3.2**

Property 3: Partial update preservation
*For any* existing size with both name_en and name_ar, when only one translation field is updated, the other translation field should remain unchanged.
**Validates: Requirements 2.3**

Property 4: Fallback behavior
*For any* size with only one translation (either name_en or name_ar), when the name attribute is accessed in any locale, it should return the available translation.
**Validates: Requirements 3.3**

Property 5: Sort order consistency
*For any* collection of sizes, when queried with the ordered scope in different locales, the sort_order should remain consistent regardless of the current locale.
**Validates: Requirements 3.4**

## Error Handling

### Validation Errors

- **Empty translations**: If both `name_en` and `name_ar` are empty, return validation error: "At least one name (English or Arabic) is required"
- **Invalid category_type**: If category_type is not in the allowed list, return validation error
- **Duplicate names**: Consider adding unique validation if business rules require unique size names per category

### Migration Errors

- If migration fails during data copy, rollback should restore the original table structure
- Log any sizes that fail to migrate for manual review

## Testing Strategy

### Unit Tests

Unit tests will cover:
- Size model creation with various translation combinations
- Validation rules for required fields
- Locale switching and name attribute retrieval
- Relationship integrity (variants, products)

### Property-Based Tests

Property-based tests will verify the correctness properties defined above using a PHP property-based testing library. We will use **Eris** (https://github.com/giorgiosironi/eris) as the property-based testing framework for PHP.

**Configuration**:
- Each property test should run a minimum of 100 iterations
- Each test must be tagged with a comment referencing the correctness property from this design document
- Tag format: `// Feature: translatable-sizes, Property {number}: {property_text}`

**Test Implementation Requirements**:
- Property 1: Generate random sizes with various translation combinations, persist them, and verify data integrity
- Property 2: Generate sizes with translations, test name retrieval in both locales
- Property 3: Create sizes, perform partial updates, verify unchanged fields remain intact
- Property 4: Generate sizes with single translations, verify fallback works in both locales
- Property 5: Create multiple sizes with different sort_orders, verify ordering consistency across locales

### Integration Tests

- Test the complete flow of creating/editing sizes through the admin interface
- Verify that size translations display correctly on the frontend
- Test migration rollback functionality

## Implementation Notes

1. **Backward Compatibility**: Existing code that accesses `$size->name` will continue to work due to the trait's accessor method
2. **API Responses**: The `$appends = ['name']` ensures JSON responses include the computed name attribute
3. **Database Indexes**: Existing indexes on the sizes table remain unchanged
4. **Admin UI**: Forms should clearly label which field is for which language (e.g., "Name (English)" and "Name (Arabic)")
5. **Migration Safety**: The migration should be tested on a copy of production data before deployment
