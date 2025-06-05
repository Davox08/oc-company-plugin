# Company & Invoicing Plugin for OctoberCMS

A professional and comprehensive invoicing system designed for OctoberCMS. This plugin allows you to manage clients, services, and generate detailed PDF invoices with automated calculations, customizable company settings, and a user-friendly backend interface.

## Key Features

* **Client Management:** Create, update, view, and delete client records.
* **Service Management:** Manage the list of services or products your company offers.
* **Invoice Management:**
    * Create detailed invoices linking clients and multiple services.
    * Automatic and customizable invoice numbering (optional prefix, date-based, sequential).
    * Automatic calculation of subtotal, tax, and total amounts.
    * Dynamic update of invoice totals in the form as services are added or modified.
* **PDF Generation:** Generate professional PDF versions of your invoices and attach them to the invoice record.
* **Customizable Settings:**
    * Configure company details (name, address, logo, contact info, GST/VAT number).
    * Set default tax rates.
    * Customize invoice number prefixes.
    * Add custom final text to invoices.
* **Backend User Permissions:** Control access to invoices, clients, services, and settings sections.
* **Standard OctoberCMS Backend UI:** Utilizes familiar list and form behaviors for a seamless experience.

## Installation

There are two main ways to install this plugin:

**1. Via OctoberCMS Marketplace (Recommended if available)**

* (Instructions would go here if your plugin is on the Marketplace)
* Typically involves searching for the plugin in the backend System > Updates & Plugins section and clicking "Install".

**2. Manual Installation**

1.  **Download/Clone Plugin:**
    * Download the plugin files (e.g., as a ZIP) or clone the repository.
    * Extract/copy the plugin folder into the `plugins/davox/company` directory within your OctoberCMS project. The path should be `plugins/davox/company/`.

2.  **Install Dependencies:**
    This plugin requires the `barryvdh/laravel-dompdf` package for PDF generation. This dependency is listed in the plugin's `composer.json`.
    * Navigate to the root directory of your OctoberCMS project in your terminal.
    * Run `composer update` or `composer install`. Composer should detect the new plugin's requirements and install `laravel-dompdf` if it's not already present in your project.
    * Alternatively, if you only want to process the plugin's `composer.json`, you might navigate to `plugins/davox/company/` and run `composer install --no-dev`, but running it from the project root is generally safer for overall dependency management.

3.  **Run Migrations:**
    Execute the plugin's database migrations to create the necessary tables:
    ```bash
    php artisan october:up
    ```
    Or, to refresh only this plugin (this will also run its migrations):
    ```bash
    php artisan plugin:refresh Davox.Company
    ```

## Configuration

1.  **Permissions:**
    * After installation, navigate to **Settings > Administrators** (or Team > Administrators).
    * Edit the roles or specific users that should have access to this plugin's features.
    * Assign the following permissions as needed:
        * `Manage invoices`
        * `Manage clients`
        * `Manage services`
        * `Manage company & tax settings`

2.  **Company Settings:**
    * Navigate to **Settings > Billing Configuration** (o el nombre que le hayas dado en `registerSettings()`).
    * Fill in your company details, upload a logo, set the default tax rate (`tax_value`), and customize the invoice prefix. These settings will be used when generating invoices and PDFs.

3.  **DomPDF Configuration (Advanced - Optional):**
    * This plugin loads its own DomPDF configuration from `plugins/davox/company/config/dompdf.php` during the `boot` process. This allows for custom font directories and other DomPDF settings specific to the plugin.
    * The plugin automatically attempts to create necessary font directories in `storage/app/davox/company/fonts` and `storage/app/davox/company/font_cache`. Ensure your `storage` directory is writable by the web server.
    * If you need to make further global changes to DomPDF, you might need to publish the main `barryvdh/laravel-dompdf` configuration file to your project's `config` directory using `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`, but be aware that this plugin's settings will try to take precedence for DomPDF options it explicitly sets.

## Usage

Once installed and configured:

1.  Navigate to the **Company** main menu item in the OctoberCMS backend.
2.  You will find sub-menu items for:
    * **Invoices:** Create new invoices, view existing ones, edit them, and export PDFs.
    * **Clients:** Manage your client database.
    * **Services:** Manage your list of services/products with their default descriptions (prices for invoices are set per line item).
3.  When creating or editing an invoice, add services. The subtotal, tax, and total will update dynamically as you modify the service line items.
4.  Use the "Export PDF" button on the invoice update form to generate and attach the PDF.

## Dependencies

* [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) (for PDF generation, managed via Composer).
* PHP extensions required by DomPDF (often `ext-gd`, `ext-mbstring`, `ext-dom`). Ensure these are enabled on your server.