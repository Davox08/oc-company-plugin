<?php

namespace Davox\Company\Controllers;

use PDF; // Alias para Barryvdh\DomPDF\Facade
use Log;
use Flash;
// No es necesario Backend; si se usa Backend::url(), pero aquí no se usa directamente.
// use Response; // Fachada Response, si la usas directamente
use Exception; // Excepción genérica de PHP
use BackendMenu;
use System\Models\File; // Modelo para adjuntar archivos
use Backend\Classes\Controller;
use Davox\Company\Models\Invoice; // Importa tu modelo Invoice
use Davox\Company\Models\Setting; // Importa tu modelo Setting

class Invoices extends Controller
{
    /**
     * @var array   List of behaviors implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\RelationController::class
    ];

    /**
     * @var string  Configuration file for the form controller.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string  Configuration file for the list controller.
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var string  Configuration file for the relation controller.
     */
    public $relationConfig = 'config_relation.yaml';

    /**
     * @var array   Permissions required to access this controller.
     */
    public $requiredPermissions = ['davox.company.access_invoices'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Davox.Company', 'company', 'invoices');

        // This script handles AJAX success events to update total fields on the invoice form.
        // It's loaded for all actions in this controller.
        $this->addJs("/plugins/davox/company/assets/js/invoice-form-updates.js", "Davox.Company");
    }

    /**
     * Handles the AJAX request to export the current invoice to PDF.
     * It generates a PDF document, attaches it to the invoice model,
     * saves the invoice, and then provides a redirect to the public URL of the PDF.
     * If 'preview' is in the input, it returns the HTML for the PDF.
     *
     * @return array|\Illuminate\Http\Response|void Can return a redirect, HTML response, or void on error.
     * @throws \Exception If PDF generation or file attachment fails critically.
     */
    public function onExportPdf()
    {
        $isPreview = (bool) request()->boolean('preview');

        $invoice = $this->formGetModel();

        if (!$invoice || !$invoice->id) {
            Flash::error(trans('Invoice not found!'));
            return;
        }

        // Eager load relations needed for the PDF.
        $invoice->load('client', 'services');

        $companySettings = Setting::first();

        if (!$companySettings) {
            Flash::error(trans('No company settings found!'));
            return;
        }

        $logoUrl = null;
        if ($companySettings->company_logo) {
            $logoUrl = $companySettings->company_logo->getThumbUrl(300, null, ['mode' => 'crop', 'quality' => 90]);
        }

        $data = [
            'invoice' => $invoice,
            'company' => [
                'logo_image'    => $logoUrl,
                'name'          => $companySettings->company_name,
                'address'       => $companySettings->company_address,
                'gst'           => $companySettings->company_gst,
                'phone'         => $companySettings->company_phone,
                'email'         => $companySettings->company_email,
                'tax_rate'      => $companySettings->tax_rate,
                'final_text'    => $companySettings->company_final_text
            ]
        ];

        $pdfHtml = $this->makePartial('invoice_pdf', $data);

        if ($isPreview) {
            return \Response::make($pdfHtml); // Using Laravel's Response facade
        }

        try {
            $pdf = PDF::loadHtml($pdfHtml);
            $filename = ($invoice->invoice_number ?: 'invoice_' . $invoice->id) . '.pdf';
            $pdfContent = $pdf->output();

            $file = (new File)->fromData($pdfContent, $filename);
            $invoice->pdf_file = $file; // Attach the file
            $invoice->save(); // Save the invoice to persist the file attachment relation

            if (!$invoice->pdf_file || !$invoice->pdf_file->exists) {
                // This exception will be caught by the catch block below.
                throw new Exception(trans('PDF generation failed!'));
            }

            Flash::success(trans('PDF generated successfully!'));

            $publicPdfUrl = $invoice->pdf_file->getUrl();

            return ['X_OCTOBER_REDIRECT' => $publicPdfUrl];

        } catch (Exception $e) {
            Log::error("[Invoices::onExportPdf] Error generating/attaching PDF for Invoice ID {$invoice->id}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            Flash::error(trans('PDF generation error!') . ': ' . $e->getMessage());
            // No return here will allow October to handle the AJAX error response,
            // or you can return an empty array or specific error structure if your JS handles it.
            return;
        }
    }

    /**
     * AJAX handler triggered by the 'changeHandler' on the 'services' relation field
     * when operating in the update context for an Invoice.
     * It reloads the services relation, recalculates invoice totals,
     * saves these totals to the database, and then returns the calculated values
     * as a JSON response for the frontend JavaScript to update the UI.
     *
     * This method is intended for use when services (line items) of an existing
     * invoice are modified (added, removed, or pivot data like quantity/price changed).
     *
     * @return array JSON response containing 'subtotalValue', 'taxValue', 'totalValue',
     * and 'X_DAVOX_COMPANY_TOTALS_UPDATED' flag.
     * Can also return an AJAX partial update for flash messages on error.
     * @throws \Exception if a critical error occurs.
     */
    public function onChangeServices()
    {
        // Essential log to confirm entry when debugging, can be removed or set to debug level for production.
        // Log::info("--- [Invoices::onChangeServices] INICIO (contexto UPDATE) ---");
        try {
            $invoice = $this->formGetModel();

            if (!$invoice || !$invoice->exists) {
                Log::warning('[Invoices::onChangeServices] Invoice model not found or not an existing record. This handler is for update context.');
                Flash::error(trans('davox.company::lang.flash.invoice_not_found_recalculate'));
                return ['#form-flash-messages' => $this->makePartial('flash_messages')];
            }

            // Log::info("[Invoices::onChangeServices] Processing for Invoice ID: " . $invoice->id); // Developmental log

            // Delegate the core logic to the model method.
            // This method reloads services and recalculates totals on the $invoice instance.
            $invoice->processExistingServicesAndRecalculateTotals();

            // Log::info("[Invoices::onChangeServices] Totals (after processExistingServicesAndRecalculateTotals): Subtotal={$invoice->subtotal}, Tax={$invoice->tax}, Total={$invoice->total}"); // Developmental log

            // Persist the calculated totals to the database.
            $isDirty = $invoice->isDirty(['subtotal', 'tax', 'total']);

            if ($isDirty) {
                // Log::info("[Invoices::onChangeServices] Saving Invoice ID {$invoice->id} with new totals (using saveQuietly)."); // Developmental log
                $invoice->saveQuietly(); // Save without firing model events to prevent potential loops.
            } else {
                // Log::info("[Invoices::onChangeServices] Totals did not change or were already persisted. No new save triggered by this handler."); // Developmental log
            }

            // Return JSON for invoice-form-updates.js to update the UI.
            return [
                'subtotalValue' => number_format($invoice->subtotal, 2, '.', ''),
                'taxValue'      => number_format($invoice->tax, 2, '.', ''),
                'totalValue'    => number_format($invoice->total, 2, '.', ''),
                'X_DAVOX_COMPANY_TOTALS_UPDATED' => true
            ];

        } catch (\Exception $e) {
            // Log the full error for backend diagnosis.
            Log::error("[Invoices::onChangeServices] Critical error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString());
            // Provide a user-friendly message.
            Flash::error(trans('davox.company::lang.flash.totals_recalculate_error_generic'));
            // Ensure the _flash_messages.htm partial exists in plugins/davox/company/controllers/invoices/
            // or that the global flash message handling for AJAX works.
            return ['#form-flash-messages' => $this->makePartial('flash_messages')];
        }
    }
}