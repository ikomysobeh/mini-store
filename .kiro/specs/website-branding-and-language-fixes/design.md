# Design Document

## Overview

This design addresses two critical UI/UX issues:
1. Improving the logo and site name styling with proper flexbox layout and responsive sizing
2. Fixing the language switcher to correctly handle URL construction without introducing `/public/` path segments or creating 404 errors

The solution involves updating the Vue components responsible for branding display and language switching logic.

## Architecture

### Component Structure

```
AppLogo.vue (Admin/Dashboard)
├── Logo Icon
└── Site Name Text (needs styling fixes)

Navbar.vue (Public Site)
├── Logo Section
│   ├── Logo Image/Icon
│   └── Site Name Text (needs styling fixes)
└── Language Switcher
    └── LanguageSwitcher.vue (needs URL logic fixes)
```

### Key Changes

1. **AppLogo.vue**: Update flex layout and responsive text sizing
2. **Navbar.vue**: Update logo section flex layout and responsive text sizing
3. **LanguageSwitcher.vue**: Fix URL construction logic to handle `/public/` removal and proper locale prefix handling

## Components and Interfaces

### 1. AppLogo Component (resources/js/components/AppLogo.vue)

**Current Issues:**
- Fixed text "Laravel Starter Kit" instead of dynamic site name
- Layout may not be optimal on mobile

**Changes:**
- Add responsive text sizing classes
- Improve flex layout for better mobile display
- Use dynamic site name from props/settings

### 2. Navbar Component (resources/js/components/Navbar.vue)

**Current Issues:**
- Site name text sizing not optimized for mobile
- Flex layout could be improved

**Changes:**
- Add responsive text sizing (smaller on mobile, larger on desktop)
- Improve flex layout with proper spacing
- Ensure text truncation works properly

### 3. LanguageSwitcher Component (resources/js/components/LanguageSwitcher.vue)

**Current Issues:**
- URL construction includes `/public/` in production
- Doesn't properly handle locale prefix removal
- Creates malformed URLs like `/ar/public/en`

**Root Cause Analysis:**
The issue occurs because:
1. `window.location.pathname` in production includes `/public/` (e.g., `/public/en`)
2. The regex `/^\/(en|ar)/` only removes locale prefixes, not `/public/`
3. When switching languages, `/public/` gets preserved and combined with new locale

**Solution:**
- Remove `/public/` from pathname before processing
- Properly strip existing locale prefix
- Correctly add new locale prefix based on configuration
- Handle default locale (ar) which should have no prefix when `hideDefaultLocaleInURL` is true

## Data Models

No database changes required. This is purely a frontend fix.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Logo text responsive sizing
*For any* viewport size, the logo text should scale appropriately and remain readable without breaking the layout
**Validates: Requirements 1.2, 1.3**

### Property 2: URL path cleaning
*For any* URL path containing `/public/`, the language switcher should remove it before constructing the new localized URL
**Validates: Requirements 2.1, 2.2, 2.4**

### Property 3: Locale prefix handling
*For any* language switch operation, the system should correctly remove the old locale prefix and add the new one (or omit it for default locale)
**Validates: Requirements 2.3, 2.5, 2.6**

### Property 4: URL construction consistency
*For any* valid current URL and target locale, the language switcher should produce a valid URL without duplicate path segments
**Validates: Requirements 2.1, 2.2, 2.3**

## Error Handling

### Language Switcher Errors

1. **Invalid URL Construction**: If URL construction fails, log error and fallback to root path with locale
2. **Missing Locale**: If target locale is invalid, prevent navigation and show console warning
3. **Path Parsing Errors**: If pathname parsing fails, use safe fallback to root path

### Styling Errors

1. **Missing Site Name**: If site name is not provided, show only logo without text
2. **Long Site Names**: Use CSS truncation to prevent layout breaking

## Testing Strategy

### Unit Tests

1. **Logo Component Tests**:
   - Test that site name displays correctly when provided
   - Test that layout doesn't break with long site names
   - Test responsive classes are applied correctly

2. **Language Switcher Tests**:
   - Test URL construction with `/public/` in path
   - Test URL construction without `/public/` in path
   - Test switching from ar to en
   - Test switching from en to ar
   - Test multiple consecutive switches
   - Test locale prefix removal
   - Test default locale URL generation (no prefix)

### Manual Testing Checklist

1. Test logo display on mobile devices (320px, 375px, 414px widths)
2. Test logo display on tablet devices (768px, 1024px widths)
3. Test logo display on desktop (1280px+ widths)
4. Test language switching in development environment
5. Test language switching in production environment
6. Test language switching on different pages (home, products, cart, checkout)
7. Test multiple consecutive language switches
8. Verify no `/public/` appears in URLs after switching
9. Verify no 404 errors occur after switching

## Implementation Notes

### CSS Classes for Responsive Text

```css
/* Mobile first approach */
text-base     /* 16px - mobile */
sm:text-lg    /* 18px - small screens */
md:text-xl    /* 20px - medium screens */
lg:text-2xl   /* 24px - large screens */
```

### URL Cleaning Logic

```javascript
// Clean pathname by removing /public/ and locale prefix
const cleanPath = pathname
  .replace(/\/public/g, '')  // Remove /public/
  .replace(/^\/(en|ar)/, '') // Remove locale prefix
  || '/';                     // Fallback to root

// Add new locale prefix (skip for default locale 'ar')
const newPath = newLocale === 'ar' 
  ? cleanPath 
  : `/${newLocale}${cleanPath}`;
```

### Configuration Awareness

The language switcher must respect the `hideDefaultLocaleInURL` configuration:
- When `true` (current setting): Arabic URLs have no prefix, English URLs have `/en`
- The switcher should check this configuration or follow the established pattern

## Dependencies

- Vue 3 composition API
- Inertia.js for navigation
- vue-i18n for locale management
- Tailwind CSS for responsive styling
- Laravel Localization package configuration

## Performance Considerations

- No performance impact expected
- URL construction is synchronous and fast
- CSS changes are minimal and don't affect rendering performance
- Full page reload on language switch is acceptable for locale changes
