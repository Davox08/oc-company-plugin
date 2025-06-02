<?php

namespace Davox\Company\Models;

/**
 * Application Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Setting extends \System\Models\SettingModel
{
    /**
     * Traits
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#validation
     *
     */
    use \October\Rain\Database\Traits\Validation;

    /**
     * Settings code
     * @link https://docs.octobercms.com/3.x/extend/settings/model-settings.html#model-class-definition
     *
     */
    public $settingsCode = 'davox_company_settings';
    public $settingsFields = 'fields.yaml';

    /**
     * Validation rules
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#validation
     * @var array $rules
     */
    public $rules = [
        // 'company_logo'     => 'nullable|image|max:2048',
        'company_name'     => 'required|string|max:100',
        'company_address'  => 'required|string|max:255',
        'company_email'    => 'nullable|string|max:100',
        'company_phone'    => 'nullable|string|max:20',
        'company_gst'      => 'nullable|string|max:50',
        'default_tax_rate' => 'required|numeric|between:0,100'
    ];

    /**
     * File attachments
     * Attach one file
     * @link https://docs.octobercms.com/3.x/extend/database/attachments.html
     *
     */
    public $attachOne = [
        'company_logo' => [\System\Models\File::class]
    ];


    /**
     * Obtiene la tasa de impuesto como decimal (ej: 16% → 0.16)
     */
    public static function getTaxFactor(): float
    {
        return (float)self::get('default_tax_rate', 16) / 100;
    }
}
