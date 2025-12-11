# CSRF Token Mismatch Fix - Post-Registration Cart Issue

## Problem Description

Users were experiencing a **419 CSRF Token Mismatch** error when attempting to add items to their cart immediately after creating a new account. The error would disappear after manually refreshing the page.

### User Flow That Triggered the Issue:
1. User creates a new account (registration)
2. User is redirected to the homepage
3. User attempts to add a product to cart
4. **Error: 419 CSRF Token Mismatch**
5. User refreshes the page manually
6. Add to cart now works correctly

---

## Root Cause Analysis

### Why This Happened:

#### 1. **Session Regeneration on Registration**
When a user registers or logs in, Laravel automatically regenerates the session ID for security purposes. This is a built-in security feature to prevent session fixation attacks.

```php
// Laravel does this internally during authentication:
$request->session()->regenerate();
```

#### 2. **Stale CSRF Token in Browser**
The CSRF token is embedded in the page's `<meta>` tag when the page first loads:

```html
<meta name="csrf-token" content="OLD_TOKEN_HERE">
```

After registration, the session is regenerated, which creates a **new CSRF token** on the server. However, the browser still has the **old token** from the initial page load.

#### 3. **Raw `fetch()` API Usage**
The `ProductDetails.vue` component was using the raw JavaScript `fetch()` API to make POST requests:

```javascript
fetch(cartAddUrl(product.id), {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        // This gets the OLD token from the meta tag
    },
    body: JSON.stringify(data)
})
```

The problem: `fetch()` reads the CSRF token from the DOM's `<meta>` tag, which still contains the **old, invalid token** after registration.

#### 4. **Why Refresh Fixed It**
When the user manually refreshed the page:
- Laravel rendered a fresh page with the **new CSRF token** in the meta tag
- The next cart request used the correct token
- Everything worked

---

## The Solution

### What We Changed:

#### 1. **Switched from `fetch()` to Inertia's `router.post()`**

**Before (Problematic Code):**
```javascript
// ❌ Using raw fetch() - manually handles CSRF
fetch(cartAddUrl(product.id), {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    },
    body: JSON.stringify(data)
})
```

**After (Fixed Code):**
```javascript
// ✅ Using Inertia router.post() - handles CSRF automatically
router.post(cartAddUrl(product.id), data, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (pageData) => {
        const flashSuccess = pageData.props?.flash?.success;
        showMessage(flashSuccess || t('productDetail.addedToCartSuccess'), 'success');
    },
    onError: (errors) => {
        const errorMsg = errors.message || Object.values(errors)[0];
        showMessage(errorMsg, 'error');
    }
});
```

**Why This Works:**
- Inertia automatically includes the correct CSRF token with every request
- Inertia manages token refresh internally
- No need to manually read from the DOM

#### 2. **Updated CartController to Support Inertia Requests**

The backend needed to handle both Inertia requests (from the product detail page) and JSON requests (from other components).

**Added Dual Response Support:**
```php
public function add(Request $request, Product $product)
{
    // ... validation and cart logic ...
    
    if ($result['success']) {
        // For Inertia requests - return redirect with flash message
        if ($request->header('X-Inertia')) {
            return back()->with('success', $result['message']);
        }
        
        // For AJAX/JSON requests - return JSON response
        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'cart_count' => $cart->items->sum('quantity')
        ]);
    }
}
```

#### 3. **Added Debug Logging for Production**

To help diagnose similar issues in the future:

```php
Log::info('🛒 Cart Add Request', [
    'product_id' => $product->id,
    'user_id' => auth()->id(),
    'session_id' => Session::getId(),
    'is_inertia' => $request->header('X-Inertia') ? true : false,
    'csrf_token_present' => $request->header('X-CSRF-TOKEN') ? true : false,
]);
```

#### 4. **Created Custom RegisterResponse**

Ensures proper session handling after registration:

```php
// app/Http/Responses/RegisterResponse.php
class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        // Force full page reload to get fresh CSRF token
        return Inertia::location('/');
    }
}
```

#### 5. **Added Global CSRF Error Handling**

In `resources/js/app.ts`, added interceptors to auto-reload on 419 errors:

```javascript
// Handle CSRF token mismatch (419 errors) - auto refresh page
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

// Also handle Inertia navigation errors
router.on('invalid', (event) => {
    if (event.detail.response.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});
```

---

## Technical Deep Dive

### How Inertia Handles CSRF Tokens

Inertia.js automatically manages CSRF tokens through its request interceptor:

1. **Token Storage**: Inertia reads the CSRF token from the `<meta>` tag on initial page load
2. **Token Refresh**: On every Inertia response, it updates its internal token if the server sends a new one
3. **Automatic Inclusion**: Every POST/PUT/PATCH/DELETE request automatically includes the current token
4. **Session Sync**: Inertia keeps the token in sync with the server's session

### Why `fetch()` Doesn't Work Well with Inertia

The raw `fetch()` API:
- Reads the token once from the DOM
- Doesn't know about session regeneration
- Doesn't automatically update when the server sends a new token
- Requires manual token management

### Laravel's Session Regeneration

Laravel regenerates sessions during:
- User login (`Auth::login()`)
- User registration (via Fortify)
- Password reset
- Any manual call to `$request->session()->regenerate()`

This is a security feature to prevent **session fixation attacks**.

---

## Files Modified

### Frontend Changes:
1. **`resources/js/components/product/ProductDetails.vue`**
   - Removed `fetch()` API usage
   - Switched to `router.post()` from Inertia
   - Updated success/error handling for Inertia responses

2. **`resources/js/app.ts`**
   - Added axios interceptor for 419 errors
   - Added Inertia router error handler

### Backend Changes:
1. **`app/Http/Controllers/Web/CartController.php`**
   - Added dual response support (Inertia + JSON)
   - Added debug logging
   - Improved error handling

2. **`app/Http/Responses/RegisterResponse.php`** (New File)
   - Custom registration response
   - Forces full page reload after registration

3. **`app/Providers/FortifyServiceProvider.php`**
   - Registered custom `RegisterResponse`

---

## Production Checklist

When deploying to production, ensure these settings in `.env`:

```env
# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=yourdomain.com    # ⚠️ Must match your actual domain
SESSION_SECURE_COOKIES=true      # ⚠️ Must be true for HTTPS
SESSION_SAME_SITE=lax
```

### Common Production Issues:

1. **`SESSION_DOMAIN` mismatch**
   - If your domain is `example.com`, set `SESSION_DOMAIN=example.com`
   - Don't include `www.` unless you want to restrict to that subdomain

2. **`SESSION_SECURE_COOKIES=false` on HTTPS**
   - If your site uses HTTPS, this MUST be `true`
   - Otherwise, cookies won't be sent

3. **Mixed HTTP/HTTPS content**
   - Ensure all assets load over HTTPS
   - Check for hardcoded `http://` URLs

4. **CDN/Proxy stripping cookies**
   - Some CDNs or proxies may strip session cookies
   - Check your CDN configuration

---

## Testing the Fix

### Manual Testing Steps:

1. **Clear browser cookies and cache**
2. **Visit the site as a guest**
3. **Create a new account**
4. **Immediately try to add a product to cart** (without refreshing)
5. **Verify**: Product should be added successfully without errors

### What to Look For in Logs:

After deploying, check `storage/logs/laravel.log` for:

```
[INFO] 🛒 Cart Add Request
{
    "product_id": 123,
    "user_id": 456,
    "session_id": "abc123...",
    "is_inertia": true,
    "csrf_token_present": true
}
```

If you see `"csrf_token_present": false`, there's still an issue with token transmission.

---

## Why This Solution is Better

### Before:
- ❌ Manual CSRF token management
- ❌ Token could become stale
- ❌ Required page refresh after registration
- ❌ Inconsistent with other cart operations
- ❌ Poor user experience

### After:
- ✅ Automatic CSRF token management
- ✅ Token always in sync with server
- ✅ Seamless experience after registration
- ✅ Consistent with Inertia best practices
- ✅ Better error handling and logging
- ✅ Excellent user experience

---

## Related Components

Other components that use cart operations correctly:

- `resources/js/components/home/FeaturedProducts.vue` - Uses `router.post()`
- `resources/js/components/products/ProductsGrid.vue` - Uses `router.post()`
- `resources/js/components/products/ProductCard.vue` - Uses `router.post()`

These components didn't have the issue because they were already using Inertia's router.

---

## Deployment Commands

```bash
# Build frontend assets
npm run build

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Restart queue workers (if using queues)
php artisan queue:restart
```

---

## Future Considerations

### If You Need to Use `fetch()` in the Future:

If you absolutely must use `fetch()` instead of Inertia's router, you need to:

1. **Listen for Inertia page updates** to refresh the token:
```javascript
router.on('navigate', () => {
    // Update your stored CSRF token
    csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
});
```

2. **Handle 419 errors gracefully**:
```javascript
if (response.status === 419) {
    // Reload page to get fresh token
    window.location.reload();
    return;
}
```

3. **Consider using axios instead** - it has better interceptor support

### Best Practice:
**Always use Inertia's router methods** (`router.post()`, `router.get()`, etc.) when working with Inertia.js applications. They handle CSRF, state management, and navigation automatically.

---

## Summary

The CSRF token mismatch issue was caused by using raw `fetch()` API which couldn't track session regeneration after user registration. The solution was to switch to Inertia's `router.post()` method, which automatically manages CSRF tokens and keeps them in sync with the server's session state.

This fix ensures a seamless user experience where users can immediately interact with the cart after registration without needing to manually refresh the page.
