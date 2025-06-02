<?php

namespace Davox\Company\Models;

use Model;

/**
 * Application Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Client extends Model
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
    protected $table = 'davox_company_clients';

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
        'name'       => 'required|min:3|max:100',
        'email'      => 'nullable|email|required_without:whatsapp|unique:davox_company_clients',
        'whatsapp'   => 'nullable|string|required_without:email|unique:davox_company_clients',
        'address'    => 'nullable|max:255',
        'gst_number' => 'nullable|string|max:50'
    ];

    /**
     * Validation custom messages
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#custom-error-messages
     * @var array $rules
     */
    public $customMessages = [
        'name.required'             => 'El nombre del cliente es obligatorio',
        'email.required_without'    => 'Debes proporcionar al menos un email o WhatsApp',
        'whatsapp.required_without' => 'Debes proporcionar al menos un WhatsApp o email',
        'email.email'               => 'El formato del email no es válido'
    ];

    /**
     * Relations - One to many
     * @link https://docs.octobercms.com/3.x/extend/database/relations.html#one-to-many
     * Client has many invoices
     */
    public $hasMany = [
        'invoices' => [
            \Davox\Company\Models\Invoice::class
        ]
    ];
}
