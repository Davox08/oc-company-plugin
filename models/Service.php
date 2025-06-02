<?php

namespace Davox\Company\Models;

use Model;

/**
 * Application Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Service extends Model
{
    /**
     * Traits
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#validation
     *
     */
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;

    /**
     * Defining model
     * @link https://docs.octobercms.com/3.x/extend/settings/model-settings.html#model-class-definition
     *
     */
    protected $table = 'davox_company_services';

    /**
     * Dates
     * @link https://docs.octobercms.com/3.x/extend/system/models.html#supported-properties
     *
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Validation rules
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#validation
     * @var array $rules
     */
    public $rules = [
        'name' => 'required|min:3|max:255|unique:davox_company_services',
    ];

    /**
     * Validation custom messages
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#custom-error-messages
     * @var array $rules
     */
    public $customMessages = [
        'name.required' => 'The service name is required',
        'name.unique' => 'The service name is already taken',
        'name.min' => 'The service name must be at least :min characters',
        'name.max' => 'The service name must be less than :max characters',
    ];

    /**
     * Relations - Many to many
     * @link https://docs.octobercms.com/3.x/extend/database/relations.html#many-to-many
     * Services belongs to many invoices
     */
    public $belongsToMany = [
        'invoices' => [
            \Davox\Company\Models\Invoice::class,
        ]
    ];
}
