<?php

namespace Davox\Company\Models;

use Model;
// use Illuminate\Support\Facades\Log;

/**
 * Service Model.
 * Represents a service offered by the company, which can be included in invoices.
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Service extends Model
{
    /**
     * @var array Traits used by this model.
     * Includes Validation for input validation and SoftDelete for soft deleting records.
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html
     */
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'davox_company_services';

    /**
     * @var array Attributes that should be mutated to dates.
     * This ensures that these fields are Carbon instances.
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * @var array Validation rules for the model attributes.
     * These rules are used when saving or validating the model.
     * - 'name' is required, must be at least 3 characters, a maximum of 255,
     * and unique in the 'davox_company_services' table.
     */
    public $rules = [
        'name' => 'required|min:3|max:255|unique:davox_company_services,name',
    ];

    /**
     * @var array Custom validation messages for the defined rules.
     * Provides user-friendly error messages.
     */
    public $customMessages = [
        'name.required' => 'The service name is required.',
        'name.unique'   => 'The service name is already taken.',
        'name.min'      => 'The service name must be at least :min characters.',
        'name.max'      => 'The service name must be less than :max characters.'
    ];

    /**
     * @var array Defines the many-to-many relationships for this model.
     * 'invoices' relationship: A service can belong to many invoices.
     * @link https://docs.octobercms.com/3.x/extend/database/relations.html#many-to-many
     */
    public $belongsToMany = [
        'invoices' => [
            \Davox\Company\Models\Invoice::class,
            'table'    => 'davox_company_invoice_service',
            'key'      => 'service_id',
            'otherKey' => 'invoice_id',
            // 'pivot' => ['price', 'quantity', 'description'],
            // 'timestamps' => true,
        ]
    ];
}