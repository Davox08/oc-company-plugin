<?php

namespace Davox\Company\Models;

use Model;

// use Illuminate\Support\Facades\Log;

/**
 * Client Model.
 * Represents a client (customer) in the system.
 * Stores client information and relationships, such as associated invoices.
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Client extends Model
{
    /**
     * @var array Traits used by this model.
     * - Validation: Enables model attribute validation.
     * - SoftDelete: Enables soft deleting records instead of permanent removal.
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html
     */
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'davox_company_clients';

    /**
     * @var array Attributes that should be mutated to dates.
     * Ensures these fields are treated as Carbon\Carbon instances.
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * @var array Attributes that should be converted from empty strings to NULL on save.
     * This is used for nullable columns with a UNIQUE database constraint to prevent
     * "duplicate entry ''" errors for multiple records with empty values.
     */
    // --- PASO 1: ASEGÚRATE DE QUE ESTA PROPIEDAD ESTÉ DEFINIDA EN TU CLASE ---
    protected $nullableUniqueAttributes = [
        'email',
        'phone',
        'gst'
    ];

    /**
     * @var array Validation rules for the model attributes.
     * These rules are automatically_context_replied when saving the model.
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#validation
     */
    public $rules = [
        'name'    => 'required|min:3|max:100',
        'email'   => 'nullable|email|required_without:phone|unique:davox_company_clients,email',
        'phone'   => 'nullable|string|max:25|required_without:email|unique:davox_company_clients,phone',
        'address' => 'nullable|string|max:255',
        'gst'     => 'nullable|string|max:50|unique:davox_company_clients,gst',
    ];

    /**
     * @var array Custom validation messages for the defined rules.
     * These provide more user-friendly feedback.
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#custom-error-messages
     */
    public $customMessages = [
        'name.required'          => 'The client name is required.',
        'email.required_without' => 'You must provide at least an email or phone.',
        'phone.required_without' => 'You must provide at least an email or phone.',
        'email.email'            => 'The email format is invalid.',
        'email.unique'           => 'This email has already been registered by another client.',
        'phone.unique'           => 'This phone has already been registered by another client.'
    ];

    /**
     * @var array Defines the "has many" relationships for this model.
     * 'invoices' relationship: A client can have many invoices.
     * @link https://docs.octobercms.com/3.x/extend/database/relations.html#one-to-many
     */
    public $hasMany = [
        'invoices' => [
            \Davox\Company\Models\Invoice::class,
            'key'      => 'client_id',
            'otherKey' => 'id'
        ]
    ];

    /**
     * Event handler for before the model is saved.
     * Iterates through $nullableUniqueAttributes and converts any empty string values to NULL.
     * This prevents unique constraint violations in the database for optional fields.
     */
    // --- PASO 2: ASEGÚRATE DE QUE ESTE MÉTODO ESTÉ EN TU CLASE ---
    public function beforeSave()
    {
        foreach ($this->nullableUniqueAttributes as $attribute) {
            if (isset($this->attributes[$attribute]) && empty(trim($this->attributes[$attribute]))) {
                $this->attributes[$attribute] = null;
            }
        }
    }
}
