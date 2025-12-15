<?php

namespace YourUsername\FormBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
    protected $fillable = [
        'title',
        'description',
        'slug',
        'fields',
        'is_active',
        'staff_email',
        'confirmation_message',
        'send_confirmation',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
        'send_confirmation' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->title) . '-' . Str::random(8);
            }
        });
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function getUrlAttribute()
    {
        return route('forms.show', $this->slug);
    }

    /**
     * Validate submission data against form fields
     */
    public function validateSubmission(array $data)
    {
        $rules = [];
        
        foreach ($this->fields as $field) {
            $fieldRules = [];
            
            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            }
            
            switch ($field['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
            }
            
            if (!empty($fieldRules)) {
                $rules[$field['name']] = $fieldRules;
            }
        }
        
        return validator($data, $rules);
    }
}
