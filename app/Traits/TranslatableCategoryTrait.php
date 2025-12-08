<?php

namespace App\Traits;

trait TranslatableCategoryTrait
{
    /**
     * Get the name attribute for the current locale.
     * Returns name_ar if locale is 'ar' and field is not null,
     * else returns name_en, else falls back to old 'name' column.
     */
    public function getNameAttribute($value)
    {
        $locale = app()->getLocale();
        
        if ($locale === 'ar' && !empty($this->attributes['name_ar'])) {
            return $this->attributes['name_ar'];
        }
        
        if (!empty($this->attributes['name_en'])) {
            return $this->attributes['name_en'];
        }
        
        // Fallback to the original name column
        return $value;
    }

    /**
     * Get the slug attribute for the current locale.
     */
    public function getSlugAttribute($value)
    {
        $locale = app()->getLocale();
        
        if ($locale === 'ar' && !empty($this->attributes['slug_ar'])) {
            return $this->attributes['slug_ar'];
        }
        
        if (!empty($this->attributes['slug_en'])) {
            return $this->attributes['slug_en'];
        }
        
        // Fallback to the original slug column
        return $value;
    }

    /**
     * Get the description attribute for the current locale.
     * Returns description_ar if locale is 'ar' and field is not null,
     * else returns description_en, else falls back to old 'description' column.
     */
    public function getDescriptionAttribute($value)
    {
        $locale = app()->getLocale();
        
        if ($locale === 'ar' && !empty($this->attributes['description_ar'])) {
            return $this->attributes['description_ar'];
        }
        
        if (!empty($this->attributes['description_en'])) {
            return $this->attributes['description_en'];
        }
        
        // Fallback to the original description column
        return $value;
    }
}
