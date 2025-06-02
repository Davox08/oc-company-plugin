<?php

namespace Davox\Company;

use App;
use Log;
use Backend;
use System\Classes\PluginBase;
use Illuminate\Support\Facades\Config;


/**
 * Plugin Information File
 *
 * @link https: //docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Company',
            'description' => 'Professional invoicing system with Email and WhatsApp integration',
            'author'      => 'Davox',
            'icon'        => 'icon-file-text-o',
            'homepage'    => 'https://dejavuu.dobermag.com'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register()
    {
        // Register the Service Provider for Dompdf
        // This ensures the Dompdf library's services are available.
        // As per OctoberCMS documentation for using Laravel packages.
        App::register(\Barryvdh\DomPDF\ServiceProvider::class);

        // Register the Facade alias for Dompdf
        // This allows you to use `PDF::` syntax in your code.
        // As per OctoberCMS documentation for using Laravel packages.
        App::registerClassAlias('PDF', \Barryvdh\DomPDF\Facade\Pdf::class);
    }

    public function boot()
    {
        // Transfer the configuration from your plugin's config/dompdf.php
        // to the main Dompdf package configuration.
        // This ensures Dompdf uses your custom settings from your plugin's config folder.
        // 'davox.company::dompdf' refers to the config file at plugins/davox/company/config/dompdf.php
        Config::set('dompdf', Config::get('davox.company::dompdf'));
    }

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
                'label' => 'Gestionar configuración de impuestos',
                'tab' => 'Company'
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'company_settings' => [
                'label' => 'Configuración de Facturación',
                'description' => 'Personalice tasas y formatos',
                'category' => 'Facturación',
                'icon' => 'icon-percent',
                'class' => \Davox\Company\Models\Setting::class,
                'order' => 100,
                'keywords' => 'impuesto factura configuración',
                'permissions' => ['davox.company.manage_settings']
            ]
        ];
    }

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
                        'icon'        => 'icon-file-text',
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
}
