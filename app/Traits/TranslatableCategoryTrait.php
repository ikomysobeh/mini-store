<?php

namespace App\Traits;

trait TranslatableCategoryTrait
{
    /**
     * Get the name attribute for the current locale.
     * Returns name_ar if locale is 'ar', else returns name_en.
     */
    public function getNameAttribute($value)
    {
        $locale = app()->getLocale();

        $nameAr = $this->attributes['name_ar'] ?? null;
        $nameEn = $this->attributes['name_en'] ?? null;

        if ($locale === 'ar' && !empty($nameAr)) {
            return $nameAr;
        }

        if (!empty($nameEn)) {
            return $nameEn;
        }

        return $value;
    }

    /**
     * Get the slug attribute.
     * Slug is language-independent (always uses English slug for URLs).
     */
    public function getSlugAttribute($value)
    {
        return $value;
    }

    /**
     * Get the description attribute for the current locale.
     * Returns description_ar if locale is 'ar', else returns description_en.
     */
    public function getDescriptionAttribute($value)
    {
        $locale = app()->getLocale();

        $descAr = $this->attributes['description_ar'] ?? null;
        $descEn = $this->attributes['description_en'] ?? null;

        if ($locale === 'ar' && !empty($descAr)) {
            return $descAr;
        }

        if (!empty($descEn)) {
            return $descEn;
        }

        return $value;
    }
}
