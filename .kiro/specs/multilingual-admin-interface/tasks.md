# Implementation Plan

- [x] 1. Update admin product forms with bilingual input fields


  - Add separate input fields for name_en and name_ar in product creation/edit forms
  - Add separate textarea fields for description_en and description_ar
  - Implement tabbed interface or side-by-side layout for language inputs
  - Add visual labels indicating which language each field represents
  - Ensure form validation requires at least one language for name and description
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 2. Update admin category forms with bilingual input fields
  - Add separate input fields for name_en and name_ar in category creation/edit forms
  - Add visual labels indicating which language each field represents
  - Ensure form validation requires at least one language for name
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [ ] 3. Implement backend validation for bilingual content
  - Update StoreProductRequest to validate bilingual fields (name_en, name_ar, description_en, description_ar)
  - Update UpdateProductRequest to validate bilingual fields
  - Update StoreCategoryRequest to validate bilingual fields (name_en, name_ar)
  - Implement "required_without" validation rule (at least one language required)
  - Add unique slug validation per language
  - _Requirements: 1.3, 2.2, 10.1, 10.2, 10.3_

- [ ]* 3.1 Write property test for bilingual validation
  - **Property 1: Bilingual Name Validation**
  - **Validates: Requirements 1.3, 2.2**

- [ ] 4. Update product controllers to handle bilingual data
  - Modify ProductController store() method to save bilingual fields
  - Modify ProductController update() method to save bilingual fields
  - Implement auto-slug generation for both languages if not provided
  - Handle validation errors and return localized error messages
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 5. Update category controllers to handle bilingual data
  - Modify CategoryController store() method to save bilingual fields
  - Modify CategoryController update() method to save bilingual fields
  - Implement auto-slug generation for both languages if not provided
  - _Requirements: 2.1, 2.2, 2.3_

- [ ]* 5.1 Write property test for slug uniqueness
  - **Property 4: Slug Uniqueness Per Language**
  - **Validates: Requirements 1.1, 2.1**

- [x] 6. Complete Arabic translations for cart page


  - Add missing translation keys for cart page UI elements to resources/js/locales/ar.json
  - Ensure cart item names display in selected language using translatable fields
  - Translate all buttons, labels, and messages on cart page
  - _Requirements: 11.4_

- [x] 7. Complete Arabic translations for checkout page



  - Add missing translation keys for checkout page UI elements to resources/js/locales/ar.json
  - Ensure product names in checkout display in selected language
  - Translate all form labels, buttons, and validation messages
  - Translate payment-related text and instructions
  - _Requirements: 11.5_

- [x] 7.1 Fix authentication route localization
  - Configure Fortify to work with locale prefix
  - Register authentication routes (login, register, password reset) with locale prefix
  - Update Fortify configuration to ignore default routes
  - Manually register localized authentication routes in web.php
  - Ensure login and register links use locale prefix
  - _Requirements: 11.1_

- [ ] 8. Complete Arabic translations for remaining user-facing pages
  - Add missing translation keys for product detail page
  - Add missing translation keys for order success/cancel pages
  - Add missing translation keys for donation pages
  - Add missing translation keys for user profile/settings pages
  - Ensure all UI elements have corresponding Arabic translations
  - _Requirements: 11.2, 11.3_

- [ ]* 8.1 Write property test for language display consistency
  - **Property 2: Language-Specific Display Consistency**
  - **Validates: Requirements 3.1, 3.2, 4.1, 4.2**

- [ ]* 8.2 Write property test for fallback behavior
  - **Property 3: Fallback Language Behavior**
  - **Validates: Requirements 3.3, 4.3**

- [ ] 9. Ensure mobile responsiveness for admin product forms
  - Apply responsive CSS classes to product form layout
  - Ensure single-column layout on mobile devices (< 768px)
  - Verify touch targets are at least 44x44 pixels
  - Test form on various mobile device sizes
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 10. Ensure mobile responsiveness for user-facing product pages
  - Apply responsive grid to product listing (1 col mobile, 2 col tablet, 3+ desktop)
  - Ensure product images scale proportionally
  - Verify readable font sizes (minimum 16px for body text)
  - Test product detail page layout on mobile
  - Ensure cart and checkout pages are mobile-friendly
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ]* 10.1 Write property test for responsive grid adaptation
  - **Property 5: Responsive Grid Column Adaptation**
  - **Validates: Requirements 6.5**

- [ ]* 10.2 Write property test for touch target sizes
  - **Property 6: Touch Target Minimum Size**
  - **Validates: Requirements 5.2, 7.5**

- [ ]* 10.3 Write property test for image scaling
  - **Property 7: Image Responsive Scaling**
  - **Validates: Requirements 6.2, 7.1**

- [ ] 11. Ensure mobile responsiveness for navigation
  - Verify hamburger menu works correctly on mobile
  - Ensure language switcher is accessible on mobile
  - Test navigation on various device sizes
  - Verify header elements (logo, cart icon) display correctly on mobile
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ]* 11.1 Write property test for mobile navigation
  - **Property 10: Mobile Navigation Accessibility**
  - **Validates: Requirements 8.1, 8.2**

- [ ] 12. Ensure mobile responsiveness for admin dashboard
  - Apply responsive layout to dashboard statistics cards
  - Ensure charts/graphs are readable on mobile
  - Implement collapsible sidebar for mobile admin navigation
  - Make data tables responsive (horizontal scroll or card layout)
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 13. Implement language persistence across navigation
  - Verify language preference persists in session/cookie
  - Test language switching across all pages
  - Ensure selected language is maintained after page refresh
  - _Requirements: 11.1_

- [ ]* 13.1 Write property test for language persistence
  - **Property 9: Language Persistence Across Navigation**
  - **Validates: Requirements 11.1**

- [ ] 14. Optimize performance for mobile devices
  - Implement lazy loading for product images
  - Add responsive image srcset for different device sizes
  - Verify page load times on 3G connection simulation
  - Optimize image sizes served to mobile devices
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 15. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
