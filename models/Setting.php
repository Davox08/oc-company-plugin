<?php

namespace Davox\Company\Models;

use October\Rain\Database\Traits\Validation;
use Illuminate\Support\Facades\Log;
use System\Models\File as SystemFile;

/**
 * Setting Model.
 * Manages plugin-specific settings, such as company details, tax rates, and invoice prefixes.
 * Utilizes OctoberCMS's SettingsModel trait to store settings efficiently.
 *
 * @link https://docs.octobercms.com/3.x/extend/settings/model-settings.html
 */
class Setting extends \System\Models\SettingModel // Extends base Model
{
    use Validation;

    /**
     * @var string A unique code for these settings. This code is used to
     * identify the settings record in the database.
     */
    public $settingsCode = 'davox_company_settings';

    /**
     * @var string Reference to the YAML configuration file that defines the
     * settings fields displayed in the backend.
     */
    public $settingsFields = 'fields.yaml';

    /**
     * @var array Validation rules for the settings fields.
     * These rules are applied when the settings are saved.
     */
    public $rules = [
        'company_name'    => 'required|string|max:100',
        'company_address' => 'required|string|max:255',
        'company_email'   => 'nullable|email|max:100',
        'company_phone'   => 'nullable|string|max:20',
        'company_gst'     => 'nullable|string|max:50',
        'tax_rate'        => 'nullable|numeric|min:0|max:100',
        'invoice_prefix'  => 'nullable|string|max:50',
    ];

    /**
     * @var array Specifies the attachOne relations for this model.
     * 'company_logo' allows a single file attachment for the company logo.
     */
    public $attachOne = [
        'company_logo' => [SystemFile::class]
    ];

    /**
     * Retrieves the tax rate from settings and returns it as a decimal factor.
     * For example, a stored tax rate of 5 (meaning 5%) will be returned as 0.05.
     * This method validates the stored value and applies a system default
     * if the configured value is missing, empty, or invalid.
     *
     * @return float The calculated tax factor (e.g., 0.05 for 5%).
     * Defaults to a system-defined percentage (e.g., 0.05 for 5% if $systemDefaultPercentage is 5.0)
     * if the setting is invalid or not explicitly set.
     */
    public static function getTaxFactor(): float
    {
        // This key MUST match the field name defined in 'models/setting/fields.yaml'
        $settingKey = 'tax_value';

        // System default percentage if the setting is not found, empty, or invalid.
        // Align this with the 'default' value in 'fields.yaml' for 'tax_value' for consistency.
        $systemDefaultPercentage = 5.0; // Example: 5% as the fallback

        // Retrieve the stored value for the setting key.
        // self::get() is provided by the SettingsModel trait.
        $storedValue = self::get($settingKey);

        $percentageToUse = $systemDefaultPercentage; // Assume system default initially

        if (!is_null($storedValue) && $storedValue !== '') {
            // A value was found; validate if it's a numeric percentage between 0 and 100.
            if (is_numeric($storedValue) && (float)$storedValue >= 0 && (float)$storedValue <= 100) {
                $percentageToUse = (float) $storedValue;
                // Log::debug("[Setting::getTaxFactor] Using stored tax value: {$percentageToUse}%."); // Optional: for detailed debugging
            } else {
                // Stored value is present but invalid (non-numeric or out of 0-100 range).
                Log::warning(
                    "[Setting::getTaxFactor] Invalid tax percentage value ('" . json_encode($storedValue) . "') " .
                        "retrieved from settings for key '{$settingKey}'. Reverting to system default: {$systemDefaultPercentage}%.",
                    [
                        'setting_key' => $settingKey,
                        'retrieved_value' => $storedValue,
                        'system_default_used' => $systemDefaultPercentage
                    ]
                );
                // $percentageToUse remains $systemDefaultPercentage
            }
        } else {
            // No value stored for $settingKey (it's null or an empty string), or it was never set.
            // Using the system default percentage.
            // This log can be changed to Log::debug if it's too verbose for production.
            Log::info(
                "[Setting::getTaxFactor] No valid value found for setting key '{$settingKey}'. " .
                    "Using system default percentage: {$systemDefaultPercentage}%.",
                [
                    'setting_key' => $settingKey,
                    'system_default_used' => $systemDefaultPercentage
                ]
            );
            // $percentageToUse remains $systemDefaultPercentage
        }

        // Convert the determined percentage to a decimal factor and round for precision.
        return round($percentageToUse / 100, 4);
    }
}
