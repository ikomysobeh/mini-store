# Implementation Plan

- [x] 1. Create database migration for translatable size columns


  - Create migration file to add name_en and name_ar columns
  - Copy existing name values to name_en column
  - Drop the old name column after data migration
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 2. Update Size model to support translations


  - Add TranslatableProductTrait to Size model
  - Update $fillable array to include name_en and name_ar
  - Add $appends array with 'name' to include computed attribute in JSON
  - Remove 'name' from $fillable array
  - _Requirements: 1.1, 1.4_

- [ ]* 2.1 Write property test for translation persistence
  - **Property 1: Translation persistence**
  - **Validates: Requirements 1.1, 1.3, 2.2**

- [ ]* 2.2 Write property test for locale-aware name retrieval
  - **Property 2: Locale-aware name retrieval**
  - **Validates: Requirements 1.4, 3.1, 3.2**

- [ ]* 2.3 Write property test for partial update preservation
  - **Property 3: Partial update preservation**
  - **Validates: Requirements 2.3**

- [ ]* 2.4 Write property test for fallback behavior
  - **Property 4: Fallback behavior**
  - **Validates: Requirements 3.3**

- [ ]* 2.5 Write property test for sort order consistency
  - **Property 5: Sort order consistency**
  - **Validates: Requirements 3.4**

- [x] 3. Update admin size controller and validation


  - Update size creation validation to require at least one name field
  - Update size update validation to handle both name_en and name_ar
  - Ensure controller stores both translation fields
  - _Requirements: 1.1, 1.3, 2.2_

- [x] 4. Update admin size forms (create and edit)



  - Add name_en input field with label "Name (English)"
  - Add name_ar input field with label "Name (Arabic)"
  - Remove old name input field
  - Update form to display both fields with proper labels
  - _Requirements: 1.2, 2.1_

- [ ]* 4.1 Write unit tests for size form validation



  - Test that at least one name is required
  - Test that both names can be provided
  - Test that single name (either language) is accepted
  - _Requirements: 1.1, 1.3_



- [ ] 5. Update any size display views to use translated names
  - Review frontend components that display size names
  - Ensure they use the computed 'name' attribute (which is locale-aware)
  - Verify size dropdowns and product variant displays work correctly


  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 6. Run migration and verify data integrity
  - Execute the migration on development database
  - Verify all existing sizes have name_en populated



  - Test creating new sizes with both translations
  - Test editing existing sizes
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [ ] 7. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
