# Complete Translation System Guide

## Overview
This application uses a **dual-layer translation system** combining Laravel backend localization with Vue.js frontend internationalization (i18n). The system supports **Arabic (ar)** as the default language and **English (en)** as a secondary language.

---

## Architecture Components

### 1. Backend (Laravel) - URL Localization
**Package**: `mcamara/laravel-localization`

#### Configuration Files
- **`config/app.php`**: Sets default locale to Arabic (`'locale' => 'ar'`)
- **`config/laravellocalization.php`**: Defines supported locales (en, es, ar)
- **`routes/web.php`**: Wraps all routes with locale prefix

#### How It Works
```php
// routes/web.php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
], function() {
    // All routes here get locale prefix
    Route::get('/', [HomeController::class, 'index'])->name('home');
});
```

**URL Structure**:
- Arabic: `https://example.com/ar/products`
- English: `https://example.com/en/products`
- Root redirect: `/` → `/ar` (default)

**Middleware Functions**:
- `localeSessionRedirect`: Remembers user's language choice in session
- `localizationRedirect`: Redirects to proper locale URL
- `localeViewPath`: Loads locale-specific Blade views if they exist

---

### 2. Frontend (Vue.js) - UI Translation
**Package**: `vue-i18n`

#### Configuration Files
- **`resources/js/i18n.ts`**: Initializes i18n instance
- **`resources/js/locales/en.json`**: English translations
- **`resources/js/locales/ar.json`**: Arabic translations
- **`resources/js/app.ts`**: Integrates i18n with Vue app

#### Translation Files Structure
```json
// resources/js/locales/en.json
{
    "nav": {
        "home": "Home",
        "products": "Products",
        "cart": "Cart"
    },
    "product": {
        "addToCart": "Add to Cart",
        "price": "Price"
    }
}

// resources/js/locales/ar.json
{
    "nav": {
        "home": "الرئيسية",
        "products": "المنتجات",
        "cart": "السلة"
    },
    "product": {
        "addToCart": "أضف إلى السلة",
        "price": "السعر"
    }
}
```

---

## Key Files Modified for Translation

### 1. **`resources/js/i18n.ts`**
```typescript
import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import ar from './locales/ar.json';

const i18n = createI18n({
    legacy: false,           // Use Composition API
    locale: 'ar',            // Default locale
    fallbackLocale: 'ar',    // Fallback if translation missing
    messages: { en, ar }
});

export default i18n;
```

### 2. **`resources/js/app.ts`**
```typescript
import i18n from './i18n';

createInertiaApp({
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n);  // Register i18n plugin

        // Sync locale from Laravel
        if (props.initialPage.props.locale) {
            i18n.global.locale.value = props.initialPage.props.locale;
        }

        app.mount(el);
    }
});
```

### 3. **`resources/js/composables/useLocale.ts`**
Helper composable for generating localized URLs:
```typescript
export function useLocale() {
    const page = usePage();
    const locale = computed(() => page.props.locale || 'en');
    
    const localizedUrl = (path: string): string => {
        const cleanPath = path.startsWith('/') ? path.slice(1) : path;
        return `/${locale.value}/${cleanPath}`;
    };
    
    return { locale, localizedUrl };
}
```

### 4. **`resources/js/components/LanguageSwitcher.vue`**
UI component for switching languages:
```vue
<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const switchLanguage = (newLocale: string) => {
    const currentPath = window.location.pathname;
    let cleanPath = currentPath
        .replace(/\/public/g, '')
        .replace(/^\/(en|ar)/, '');
    
    const newPath = `/${newLocale}${cleanPath || '/'}`;
    window.location.href = newPath;  // Full page reload
};
</script>
```

**Why full page reload?**
- Ensures Laravel backend updates session locale
- Refreshes CSRF token for the new locale
- Reloads all server-side data with correct language

---

## How to Use Translations in Components

### Option 1: Composition API (Recommended)
```vue
<script setup lang="ts">
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <h1>{{ t('nav.home') }}</h1>
    <button>{{ t('product.addToCart') }}</button>
</template>
```

### Option 2: Template Syntax
```vue
<template>
    <h1>{{ $t('nav.home') }}</h1>
    <p>{{ $t('product.price') }}: $100</p>
</template>
```

### Option 3: With Variables
```vue
<template>
    <!-- Translation with dynamic value -->
    <p>{{ $t('cart.itemsInCart', { count: 5 }) }}</p>
</template>
```

Translation file:
```json
{
    "cart": {
        "itemsInCart": "{count} items in cart"
    }
}
```

---

## How Language Switching Works (Step-by-Step)

### User Clicks Language Switcher

1. **Frontend (LanguageSwitcher.vue)**:
   ```typescript
   switchLanguage('en')
   // Current URL: /ar/products/shoes
   // Extracts path: /products/shoes
   // Builds new URL: /en/products/shoes
   window.location.href = '/en/products/shoes'
   ```

2. **Backend (Laravel)**:
   ```php
   // Middleware intercepts request
   LaravelLocalization::setLocale() // Sets locale to 'en'
   app()->setLocale('en')           // Updates Laravel locale
   session(['locale' => 'en'])      // Saves to session
   ```

3. **Frontend (app.ts)**:
   ```typescript
   // Inertia passes locale in props
   props.initialPage.props.locale = 'en'
   
   // Vue i18n updates
   i18n.global.locale.value = 'en'
   ```

4. **Result**:
   - URL: `/en/products/shoes`
   - All `$t()` calls use English translations
   - Laravel uses English for server-side messages
   - Session remembers choice for future visits

---

## Database Translation (Models)

Some models store translations in JSON columns:

### Example: Category Model
```php
// database/migrations/xxx_create_categories_table.php
$table->json('name');  // Stores: {"en": "Shoes", "ar": "أحذية"}
$table->json('description')->nullable();

// app/Models/Category.php
protected $casts = [
    'name' => 'array',
    'description' => 'array',
];

// Usage in Controller
$category->name[app()->getLocale()]  // Returns localized name
```

### Example: Product Model
```php
// Translatable fields
protected $casts = [
    'name' => 'array',
    'description' => 'array',
];

// In Blade/Inertia
{{ $product->name[app()->getLocale()] }}
```

---

## Payment Routes and Localization

### Important: Payment Webhooks Don't Use Locale Prefix

```php
// routes/api.php (NO locale prefix)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
```

**Why?**
- Stripe/PayPal servers call webhooks directly
- They don't know about your locale system
- Webhooks must be at fixed, predictable URLs

### Payment Success/Cancel Routes (WITH locale prefix)

```php
// routes/web.php (INSIDE locale group)
Route::get('/payment/success', [OrderController::class, 'paymentSuccess'])
    ->name('payment.success');
Route::get('/payment/cancel', [OrderController::class, 'paymentCancel'])
    ->name('payment.cancel');
```

**URLs**:
- Arabic: `/ar/payment/success`
- English: `/en/payment/success`

### Creating Stripe Checkout Session
```php
// In your controller
$session = \Stripe\Checkout\Session::create([
    'success_url' => route('payment.success'),  // Auto-includes locale
    'cancel_url' => route('payment.cancel'),
    // ...
]);
```

Laravel's `route()` helper automatically includes the current locale prefix.

---

## Adding New Translations

### Step 1: Add to JSON Files
```json
// resources/js/locales/en.json
{
    "newFeature": {
        "title": "New Feature",
        "description": "This is a new feature"
    }
}

// resources/js/locales/ar.json
{
    "newFeature": {
        "title": "ميزة جديدة",
        "description": "هذه ميزة جديدة"
    }
}
```

### Step 2: Use in Components
```vue
<template>
    <h2>{{ $t('newFeature.title') }}</h2>
    <p>{{ $t('newFeature.description') }}</p>
</template>
```

### Step 3: Test Both Languages
1. Visit `/ar/your-page` - Should show Arabic
2. Visit `/en/your-page` - Should show English
3. Use language switcher - Should update correctly

---

## Common Issues and Solutions

### Issue 1: Translation Not Showing
**Problem**: `{{ $t('nav.home') }}` shows `nav.home` instead of translation

**Solutions**:
1. Check JSON file has the key: `"nav": { "home": "..." }`
2. Verify no typos in key name
3. Check i18n is imported in `app.ts`
4. Clear browser cache

### Issue 2: Wrong Language After Switch
**Problem**: Switched to English but still seeing Arabic

**Solutions**:
1. Check `LanguageSwitcher.vue` does full page reload
2. Verify middleware is applied in `routes/web.php`
3. Clear session: `php artisan session:clear`
4. Check browser console for JavaScript errors

### Issue 3: Payment Redirect Wrong Language
**Problem**: After payment, redirected to wrong language

**Solution**:
```php
// In payment controller
$locale = app()->getLocale();
$successUrl = url("/{$locale}/payment/success");

\Stripe\Checkout\Session::create([
    'success_url' => $successUrl,
    // ...
]);
```

### Issue 4: CSRF Token Mismatch After Language Switch
**Problem**: Forms fail after switching language

**Solution**: Already handled in `app.ts`:
```typescript
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            window.location.reload();  // Refresh CSRF token
        }
        return Promise.reject(error);
    }
);
```

---

## Translation Best Practices

### 1. Organize by Feature
```json
{
    "auth": { ... },
    "product": { ... },
    "cart": { ... },
    "checkout": { ... }
}
```

### 2. Use Descriptive Keys
❌ Bad: `"msg1": "Welcome"`
✅ Good: `"welcomeMessage": "Welcome"`

### 3. Keep Translations Consistent
If "Add to Cart" is used multiple times, use the same key:
```json
{
    "product": {
        "addToCart": "Add to Cart"
    }
}
```

### 4. Handle Pluralization
```json
{
    "cart": {
        "items": "no items | one item | {count} items"
    }
}
```

Usage:
```vue
{{ $t('cart.items', { count: cartItems.length }) }}
```

### 5. RTL Support for Arabic
```css
[dir="rtl"] {
    text-align: right;
    direction: rtl;
}
```

---

## Files Summary

### Backend Files
- `config/app.php` - Default locale
- `config/laravellocalization.php` - Supported locales
- `routes/web.php` - Locale routing
- `routes/api.php` - Non-localized routes (webhooks)

### Frontend Files
- `resources/js/i18n.ts` - i18n setup
- `resources/js/app.ts` - i18n integration
- `resources/js/locales/en.json` - English translations
- `resources/js/locales/ar.json` - Arabic translations
- `resources/js/composables/useLocale.ts` - Locale helpers
- `resources/js/components/LanguageSwitcher.vue` - Language switcher UI

### All Vue Components Using Translations
- `resources/js/components/Navbar.vue`
- `resources/js/components/home/HeroSection.vue`
- `resources/js/components/product/ProductDetails.vue`
- `resources/js/components/cart/CartSidebar.vue`
- `resources/js/pages/Web/Checkout.vue`
- `resources/js/pages/Web/Products.vue`
- And many more...

---

## Testing Checklist

- [ ] Root URL (`/`) redirects to `/ar`
- [ ] Language switcher changes URL and content
- [ ] All pages work in both `/ar/` and `/en/` URLs
- [ ] Forms submit correctly in both languages
- [ ] Payment redirects maintain language
- [ ] Session remembers language choice
- [ ] No console errors when switching languages
- [ ] Database content shows correct language
- [ ] Email notifications use correct language
- [ ] Admin panel uses correct language

---

## Conclusion

This translation system provides:
- ✅ URL-based language switching (`/ar/`, `/en/`)
- ✅ Frontend UI translations (Vue i18n)
- ✅ Backend locale awareness (Laravel)
- ✅ Database content localization (JSON columns)
- ✅ Session persistence (remembers choice)
- ✅ Payment integration compatibility
- ✅ RTL support for Arabic

The system is fully functional and ready for production use!
