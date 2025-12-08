# سجل التغييرات - واجهة الإدارة متعددة اللغات

## نظرة عامة

هذا الملف يوثق جميع التغييرات التي تمت على المشروع منذ آخر رفع على GitHub، وتتمحور حول إضافة دعم كامل للغة العربية والإنجليزية في منصة التجارة الإلكترونية للتبرعات.

---

## 1. تغييرات قاعدة البيانات (Database Migrations)

### 1.1 جدول المنتجات (Products Table)
**الملف:** `database/migrations/2025_11_22_150243_add_translatable_fields_to_products_table.php`

تمت إضافة الحقول التالية:
| الحقل | النوع | الوصف |
|-------|------|-------|
| `name_ar` | string | اسم المنتج بالعربية |
| `name_en` | string | اسم المنتج بالإنجليزية |
| `slug_ar` | string (unique) | الرابط المختصر بالعربية |
| `slug_en` | string (unique) | الرابط المختصر بالإنجليزية |
| `description_ar` | text | وصف المنتج بالعربية |
| `description_en` | text | وصف المنتج بالإنجليزية |

**ملاحظة:** تم نقل البيانات الموجودة تلقائياً إلى حقول `*_en` للحفاظ على التوافق.

### 1.2 جدول الفئات (Categories Table)
**الملف:** `database/migrations/2025_11_22_150243_add_translatable_fields_to_categories_table.php`

تمت إضافة الحقول التالية:
| الحقل | النوع | الوصف |
|-------|------|-------|
| `name_ar` | string | اسم الفئة بالعربية |
| `name_en` | string | اسم الفئة بالإنجليزية |
| `slug_ar` | string (unique) | الرابط المختصر بالعربية |
| `slug_en` | string (unique) | الرابط المختصر بالإنجليزية |

### 1.3 وصف الفئات (Category Descriptions)
**الملف:** `database/migrations/2025_12_08_100000_add_description_translations_to_categories_table.php`

تمت إضافة:
| الحقل | النوع | الوصف |
|-------|------|-------|
| `description_ar` | text | وصف الفئة بالعربية |
| `description_en` | text | وصف الفئة بالإنجليزية |

---

## 2. Traits للترجمة التلقائية

### 2.1 TranslatableProductTrait
**الملف:** `app/Traits/TranslatableProductTrait.php`

يوفر هذا الـ Trait الوظائف التالية:
- `getNameAttribute()` - يُرجع الاسم حسب اللغة الحالية
- `getSlugAttribute()` - يُرجع الرابط المختصر حسب اللغة الحالية
- `getDescriptionAttribute()` - يُرجع الوصف حسب اللغة الحالية

**آلية العمل:**
1. إذا كانت اللغة عربية (`ar`) والحقل العربي موجود → يُرجع القيمة العربية
2. إذا كان الحقل الإنجليزي موجود → يُرجع القيمة الإنجليزية
3. كـ fallback → يُرجع القيمة من العمود الأصلي

### 2.2 TranslatableCategoryTrait
**الملف:** `app/Traits/TranslatableCategoryTrait.php`

نفس الوظائف السابقة للفئات:
- `getNameAttribute()`
- `getSlugAttribute()`
- `getDescriptionAttribute()`

---

## 3. الترجمات العربية (Arabic Translations)

### 3.1 ملف الترجمة الرئيسي
**الملف:** `resources/js/locales/ar.json`

تمت إضافة ترجمات شاملة للأقسام التالية:

#### التنقل (nav)
- الرئيسية، المنتجات، السلة، الدفع، الطلبات
- تسجيل الدخول، التسجيل، تسجيل الخروج، لوحة الإدارة

#### الصفحة الرئيسية (home)
- رسائل الترحيب
- المنتجات المميزة
- الدعم التقني ووسائل التواصل

#### الشريط الجانبي (sidebar)
- سلة التسوق
- تفاصيل المنتج
- التوفر والمخزون
- التسوق حسب الفئة

#### المنتجات (product & productDetail)
- إضافة للسلة
- اختيار اللون والمقاس
- معلومات التوصيل
- رسائل التحذير والنجاح

#### سلة التسوق (cart) ✅ مكتمل
- عنوان السلة وحالتها
- المجموع الفرعي والإجمالي
- أزرار الدفع والمتابعة
- رسائل السلة الفارغة

#### صفحة الدفع (checkout) ✅ مكتمل
- معلومات العميل/المتبرع
- طرق الدفع (Stripe, PayPal)
- ملخص الطلب
- رسائل النجاح والإلغاء
- رموز الدول ومعلومات الاتصال
- مزايا وضع التبرع

#### الطلبات (orders)
- طلباتي وتتبع السجل
- حالات الطلب (قيد الانتظار، تم الشحن، إلخ)
- تصفية الطلبات
- ملخص الخيارات

#### المصادقة (auth)
- تسجيل الدخول والتسجيل
- نسيت كلمة المرور
- رسائل الترحيب

#### عام (common & messages)
- أزرار عامة (حفظ، إلغاء، حذف، تعديل)
- رسائل النجاح والخطأ

---

## 4. المهام المكتملة (Completed Tasks)

حسب ملف `tasks.md`:

### ✅ المهمة 1: تحديث نماذج المنتجات في لوحة الإدارة
- إضافة حقول منفصلة للاسم بالعربية والإنجليزية
- إضافة حقول منفصلة للوصف بالعربية والإنجليزية
- تنفيذ واجهة بتبويبات أو تخطيط جنباً إلى جنب
- إضافة تسميات واضحة لكل لغة
- التحقق من إدخال لغة واحدة على الأقل

### ✅ المهمة 6: ترجمة صفحة السلة
- إضافة مفاتيح الترجمة لعناصر واجهة صفحة السلة
- عرض أسماء المنتجات باللغة المحددة
- ترجمة جميع الأزرار والتسميات والرسائل

### ✅ المهمة 7: ترجمة صفحة الدفع
- إضافة مفاتيح الترجمة لعناصر واجهة صفحة الدفع
- عرض أسماء المنتجات باللغة المحددة
- ترجمة تسميات النماذج والأزرار ورسائل التحقق
- ترجمة النصوص المتعلقة بالدفع

### ✅ المهمة 7.1: إصلاح توطين مسارات المصادقة
- تكوين Fortify للعمل مع بادئة اللغة
- تسجيل مسارات المصادقة مع بادئة اللغة
- تحديث إعدادات Fortify لتجاهل المسارات الافتراضية
- تسجيل مسارات المصادقة المحلية يدوياً في web.php
- التأكد من أن روابط تسجيل الدخول والتسجيل تستخدم بادئة اللغة

---

## 5. المهام المتبقية (Remaining Tasks)

### قيد الانتظار:
- [ ] المهمة 2: تحديث نماذج الفئات في لوحة الإدارة
- [ ] المهمة 3: تنفيذ التحقق من المحتوى ثنائي اللغة في الخلفية
- [ ] المهمة 4: تحديث متحكمات المنتجات
- [ ] المهمة 5: تحديث متحكمات الفئات
- [ ] المهمة 8: إكمال الترجمات للصفحات المتبقية
- [ ] المهمة 9-12: التجاوب مع الأجهزة المحمولة
- [ ] المهمة 13: استمرار اللغة عبر التنقل
- [ ] المهمة 14: تحسين الأداء للأجهزة المحمولة

---

## 6. البنية التقنية

### التقنيات المستخدمة:
- **Backend:** Laravel (PHP)
- **Frontend:** Vue.js مع Inertia.js
- **CSS:** Tailwind CSS
- **قاعدة البيانات:** MySQL/PostgreSQL

### نمط الترجمة:
```
اللغة الحالية (ar/en)
       ↓
   Trait Accessor
       ↓
   ┌─────────────────┐
   │ هل اللغة عربية؟ │
   └────────┬────────┘
            │
      نعم ──┴── لا
       ↓        ↓
   name_ar   name_en
       ↓        ↓
   (fallback إذا فارغ)
```

### دعم RTL:
```html
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
```

---

## 7. ملاحظات مهمة

1. **التوافق العكسي:** جميع الحقول الجديدة `nullable` للحفاظ على التوافق مع البيانات القديمة

2. **Fallback:** إذا لم تتوفر الترجمة للغة المحددة، يتم عرض اللغة المتاحة تلقائياً

3. **Slugs فريدة:** كل لغة لها slug فريد خاص بها لتحسين SEO

4. **الأداء:** يتم تحميل الترجمات من ملف JSON واحد لتقليل طلبات الشبكة

---

## 8. كيفية الاستخدام

### للمطورين:
```php
// الحصول على اسم المنتج باللغة الحالية
$product->name; // يُرجع name_ar أو name_en تلقائياً

// تغيير اللغة
app()->setLocale('ar');
$product->name; // الآن يُرجع الاسم العربي
```

### في Vue.js:
```vue
<template>
  <h1>{{ $t('product.addToCart') }}</h1>
</template>
```

---

## 9. الخطوات التالية الموصى بها

1. إكمال نماذج الفئات في لوحة الإدارة (المهمة 2)
2. تنفيذ التحقق من الصحة في الخلفية (المهمة 3)
3. اختبار جميع الصفحات على الأجهزة المحمولة
4. إضافة الترجمات المتبقية للصفحات الأخرى

---

---

## 10. قائمة الملفات المعدلة والجديدة

### الملفات الجديدة (New Files):

#### Backend (PHP):
| الملف | الوصف |
|-------|-------|
| `app/Traits/TranslatableProductTrait.php` | Trait للترجمة التلقائية للمنتجات |
| `app/Traits/TranslatableCategoryTrait.php` | Trait للترجمة التلقائية للفئات |
| `app/Http/Controllers/Web/PayPalController.php` | متحكم PayPal للدفع |
| `app/Services/PayPalService.php` | خدمة PayPal |
| `config/laravellocalization.php` | إعدادات التوطين |

#### Database Migrations:
| الملف | الوصف |
|-------|-------|
| `2025_11_22_..._add_translatable_fields_to_products_table.php` | حقول الترجمة للمنتجات |
| `2025_11_22_..._add_translatable_fields_to_categories_table.php` | حقول الترجمة للفئات |
| `2025_11_22_..._add_multilingual_names_to_cart_items_table.php` | أسماء متعددة اللغات لعناصر السلة |
| `2025_11_22_..._add_multilingual_names_to_order_items_table.php` | أسماء متعددة اللغات لعناصر الطلب |
| `2025_12_08_..._add_description_translations_to_categories_table.php` | وصف الفئات بلغتين |

#### Frontend (Vue.js/TypeScript):
| الملف | الوصف |
|-------|-------|
| `resources/js/i18n.ts` | إعداد نظام الترجمة i18n |
| `resources/js/composables/useLocale.ts` | Composable لإدارة اللغة |
| `resources/js/components/LanguageSwitcher.vue` | مكون تبديل اللغة |
| `resources/js/locales/ar.json` | ملف الترجمة العربية |
| `resources/js/locales/en.json` | ملف الترجمة الإنجليزية |

---

### الملفات المعدلة (Modified Files):

#### Controllers (المتحكمات):
| الملف | التعديلات |
|-------|----------|
| `app/Http/Controllers/Admin/ProductController.php` | دعم الحقول ثنائية اللغة |
| `app/Http/Controllers/Admin/CategoryController.php` | دعم الحقول ثنائية اللغة |
| `app/Http/Controllers/Web/CartController.php` | عرض أسماء المنتجات باللغة المحددة |
| `app/Http/Controllers/Web/CheckoutController.php` | دعم الترجمة في صفحة الدفع |
| `app/Http/Controllers/Web/OrderController.php` | عرض الطلبات باللغة المحددة |

#### Models (النماذج):
| الملف | التعديلات |
|-------|----------|
| `app/Models/Product.php` | إضافة TranslatableProductTrait |
| `app/Models/Category.php` | إضافة TranslatableCategoryTrait |
| `app/Models/CartItem.php` | دعم الأسماء متعددة اللغات |
| `app/Models/OrderItem.php` | دعم الأسماء متعددة اللغات |

#### Middleware & Providers:
| الملف | التعديلات |
|-------|----------|
| `app/Http/Middleware/HandleInertiaRequests.php` | تمرير اللغة الحالية للـ Frontend |
| `app/Providers/FortifyServiceProvider.php` | دعم مسارات المصادقة المحلية |
| `bootstrap/app.php` | إعدادات التوطين |

#### Configuration (الإعدادات):
| الملف | التعديلات |
|-------|----------|
| `config/fortify.php` | تعطيل المسارات الافتراضية |
| `config/services.php` | إعدادات PayPal |
| `routes/web.php` | مسارات المصادقة مع بادئة اللغة |

#### Vue Components (مكونات Vue):

**صفحات الإدارة (Admin):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/pages/Admin/Products/Create.vue` | نموذج إنشاء منتج ثنائي اللغة |
| `resources/js/pages/Admin/Products/Edit.vue` | نموذج تعديل منتج ثنائي اللغة |
| `resources/js/pages/Admin/Categories/Create.vue` | نموذج إنشاء فئة ثنائي اللغة |
| `resources/js/pages/Admin/Categories/Edit.vue` | نموذج تعديل فئة ثنائي اللغة |
| `resources/js/components/admin/BasicInformationForm.vue` | نموذج المعلومات الأساسية |
| `resources/js/components/admin/products/BasicProductForm.vue` | نموذج المنتج الأساسي |

**صفحات المستخدم (Web):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/pages/Web/Home.vue` | ترجمة الصفحة الرئيسية |
| `resources/js/pages/Web/Products.vue` | ترجمة صفحة المنتجات |
| `resources/js/pages/Web/ProductDetail.vue` | ترجمة تفاصيل المنتج |
| `resources/js/pages/Web/Cart.vue` | ترجمة صفحة السلة |
| `resources/js/pages/Web/Checkout.vue` | ترجمة صفحة الدفع |
| `resources/js/pages/Web/Orders/Index.vue` | ترجمة صفحة الطلبات |
| `resources/js/pages/Web/OrderSuccess.vue` | ترجمة صفحة نجاح الطلب |
| `resources/js/pages/Web/PaymentCancel.vue` | ترجمة صفحة إلغاء الدفع |

**صفحات المصادقة (Auth):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/pages/auth/Login.vue` | ترجمة صفحة تسجيل الدخول |
| `resources/js/pages/auth/Register.vue` | ترجمة صفحة التسجيل |

**مكونات مشتركة (Components):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/components/Navbar.vue` | شريط التنقل مع تبديل اللغة |
| `resources/js/components/AppSidebar.vue` | الشريط الجانبي |
| `resources/js/components/common/Pagination.vue` | ترقيم الصفحات |

**مكونات السلة (Cart):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/components/cart/CartItemsList.vue` | قائمة عناصر السلة |
| `resources/js/components/cart/CartPageHeader.vue` | رأس صفحة السلة |
| `resources/js/components/cart/EmptyCart.vue` | السلة الفارغة |
| `resources/js/components/cart/OrderSummary.vue` | ملخص الطلب |

**مكونات الدفع (Checkout):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/components/checkout/CartSummary.vue` | ملخص السلة |
| `resources/js/components/checkout/CheckoutContent.vue` | محتوى الدفع |
| `resources/js/components/checkout/EmptyCartState.vue` | حالة السلة الفارغة |
| `resources/js/components/checkout/PurchaseTypeSelector.vue` | اختيار نوع الشراء |

**مكونات المنتجات (Products):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/components/products/ProductCard.vue` | بطاقة المنتج |
| `resources/js/components/products/ProductsGrid.vue` | شبكة المنتجات |
| `resources/js/components/products/ProductFilters.vue` | فلاتر المنتجات |
| `resources/js/components/products/PageHeader.vue` | رأس الصفحة |
| `resources/js/components/product/ProductDescription.vue` | وصف المنتج |
| `resources/js/components/product/ProductDetails.vue` | تفاصيل المنتج |
| `resources/js/components/product/RelatedProducts.vue` | المنتجات ذات الصلة |

**مكونات الصفحة الرئيسية (Home):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/components/home/FeaturedProducts.vue` | المنتجات المميزة |
| `resources/js/components/home/HeroSection.vue` | قسم البطل |
| `resources/js/components/home/HomeSidebar.vue` | الشريط الجانبي للرئيسية |

**مكونات الطلبات (Orders):**
| الملف | التعديلات |
|-------|----------|
| `resources/js/components/orders/OrderCard.vue` | بطاقة الطلب |
| `resources/js/components/orders/OrderFilters.vue` | فلاتر الطلبات |
| `resources/js/components/orders/OrderStats.vue` | إحصائيات الطلبات |

#### ملفات أخرى:
| الملف | التعديلات |
|-------|----------|
| `resources/js/app.ts` | تهيئة i18n |
| `resources/views/app.blade.php` | دعم RTL |
| `composer.json` | إضافة حزم التوطين |
| `package.json` | إضافة vue-i18n |

---

## 11. إحصائيات التغييرات

| النوع | العدد |
|-------|-------|
| ملفات جديدة | ~15 ملف |
| ملفات معدلة | ~55 ملف |
| مفاتيح ترجمة عربية | ~300+ مفتاح |
| Migrations جديدة | 5 |
| Traits جديدة | 2 |

---

## 12. أوامر مهمة للتشغيل

```bash
# تشغيل الـ migrations
php artisan migrate

# مسح الـ cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# تجميع الـ assets
npm run build

# أو للتطوير
npm run dev
```

---

*آخر تحديث: 8 ديسمبر 2025*
