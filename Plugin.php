<?php

namespace Davox\Company;

use App;
use Illuminate\Support\Facades\Log;
use Backend;
use System\Classes\PluginBase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

/**
 * Company Plugin Information File.
 *
 * This file registers the plugin with the OctoberCMS system,
 * handles plugin initialization, navigation, permissions, and settings.
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array Plugin details array.
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Company',
            'description' => 'A comprehensive and professional invoicing system for OctoberCMS. Manage clients, services, and generate PDF invoices with automated calculations, customizable company settings, and dynamic updates.',
            'author'      => 'Davox',
            'icon'        => 'icon-file-text-o',
            'homepage'    => 'https://dejavuu.dobermag.com'
        ];
    }

    /**
     * Registers any services or class aliases used by this plugin.
     * This method is called once when the plugin is first registered.
     * Here, it registers the DomPDF service provider and facade.
     *
     * @return void
     */
    public function register()
    {
        // Register the Service Provider for Dompdf.
        // This ensures the Dompdf library's services are available to the application.
        App::register(\Barryvdh\DomPDF\ServiceProvider::class);

        // Register the Facade alias for Dompdf.
        // This allows using the `PDF::` shorthand syntax for the Dompdf facade.
        App::registerClassAlias('PDF', \Barryvdh\DomPDF\Facade\Pdf::class);
    }

    /**
     * Boots (initializes) the plugin.
     * This method is called right before a request is routed.
     * It sets up plugin configurations and ensures necessary directories exist.
     *
     * @return void
     */
    public function boot()
    {
        Config::set('dompdf', Config::get('davox.company::dompdf', []));

        // Ensure required directories for functionalities like PDF font storage exist.
        $this->ensureDirectoriesExist();
    }

    /**
     * Registers user permissions offered by this plugin.
     * These permissions can be managed in the backend settings area.
     *
     * @return array Array of permission codes and their descriptions.
     */
    public function registerPermissions()
    {
        return [
            'davox.company.access_invoices' => [
                'label' => 'Manage invoices',
                'tab'   => 'Company'
            ],
            'davox.company.access_clients' => [
                'label' => 'Manage clients',
                'tab'   => 'Company'
            ],
            'davox.company.access_services' => [
                'label' => 'Manage services',
                'tab'   => 'Company'
            ],
            'davox.company.access_settings' => [
                'label' => 'Manage company & tax settings',
                'tab'   => 'Company'
            ],
        ];
    }

    /**
     * Registers a new settings page for this plugin in the backend.
     *
     * @return array Settings page definition.
     */
    public function registerSettings()
    {
        return [
            'company_settings' => [
                'label'       => 'Billing Configuration',
                'description' => 'Customize tax rates, invoice formats, and company details.',
                'category'    => 'Billing',
                'icon'        => 'icon-cog',
                'class'       => \Davox\Company\Models\Setting::class,
                'order'       => 100,
                'keywords'    => 'tax invoice configuration settings company',
                'permissions' => ['davox.company.access_settings']
            ]
        ];
    }

    /**
     * Registers backend navigation menu items for this plugin.
     *
     * @return array Navigation structure.
     */
    public function registerNavigation()
    {
        return [
            'company' => [
                'label'       => 'Company',
                'url'         => Backend::url('davox/company/invoices'),
                'icon'        => 'icon-building',
                'permissions' => ['davox.company.*'],
                'order'       => 500,

                'sideMenu' => [
                    'invoices' => [
                        'label'       => 'Invoices',
                        'icon'        => 'icon-file-text-o',
                        'url'         => Backend::url('davox/company/invoices'),
                        'permissions' => ['davox.company.access_invoices']
                    ],
                    'clients' => [
                        'label'       => 'Clients',
                        'icon'        => 'icon-users',
                        'url'         => Backend::url('davox/company/clients'),
                        'permissions' => ['davox.company.access_clients']
                    ],
                    'services' => [
                        'label'       => 'Services',
                        'icon'        => 'icon-list-alt',
                        'url'         => Backend::url('davox/company/services'),
                        'permissions' => ['davox.company.access_services']
                    ]
                ]
            ]
        ];
    }

    /**
     * Ensures that required directories for plugin functionalities (e.g., DomPDF fonts) exist.
     * Creates them if they are not found.
     * This method is called during the plugin's boot process.
     *
     * @return void
     */
    protected function ensureDirectoriesExist(): void
    {
        $directories = [
            storage_path('app/davox/company/fonts'),      // For custom PDF fonts
            storage_path('app/davox/company/font_cache'), // For DomPDF's font cache
            storage_path('temp/dompdf'),                  // For DomPDF temporary files
        ];

        foreach ($directories as $path) {
            if (!File::isDirectory($path)) {
                try {
                    // Create the directory recursively with 0775 permissions.
                    // 0775: rwxrwxr-x (owner: rwx, group: rwx, other: rx)
                    // The 'true' for recursive and 'true' for force (to try to set mode).
                    File::makeDirectory($path, 0775, true, true);
                } catch (\Exception $e) {
                    // Log an error if directory creation fails.
                    Log::error(
                        "[Davox.Company Plugin] Failed to create directory: {$path}. Error: " . $e->getMessage(),
                        ['path' => $path, 'exception' => $e]
                    );
                }
            }
        }
    }
}