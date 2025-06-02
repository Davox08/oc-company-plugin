<?php

namespace Davox\Company\Controllers;

use PDF;
use Log;
use Flash;
use Backend;
use Redirect;
use Response;
use Exception;
use BackendMenu;
use System\Models\File;
use Backend\Classes\Controller;

use Davox\Company\Models\Setting;

class Invoices extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\RelationController::class
    ];

    public $formConfig     = 'config_form.yaml';
    public $listConfig     = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public $requiredPermissions = ['davox.company.access_invoices'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Davox.Company', 'company', 'invoices');

        // This script listens for AJAX success events and updates the total fields directly.
        $this->addJS("/plugins/davox/company/assets/js/invoice-form-updates.js");
    }

    /**
     * AJAX handler to export the current invoice to PDF.
     * Generates a PDF, attaches it to the invoice model, and redirects to its public path.
     */
    public function onExportPdf()
    {
        $isPreview = (bool)\Input::get('preview');

        $invoice = $this->formGetModel();

        if (!$invoice || !$invoice->id) {
            Flash::error('Invoice not found or cannot be exported.');
            return;
        }

        $invoice->load('client', 'services');

        $companySettings = Setting::first(); // Obtiene la primera (y usualmente única) instancia del modelo Setting

        if (!$companySettings) {
            \Flash::error('Company settings not found. Please configure them in the backend.');
            return;
        }

        $data = [
            'invoice' => $invoice,
            'company' => [
                'name'    => $companySettings->company_name,
                'address' => $companySettings->company_address,
                'gst'     => $companySettings->company_gst,
                'phone'   => $companySettings->company_phone,
                'email'   => $companySettings->company_email,   // No tienes email en tu modelo Setting, así que lo dejamos fijo.
            ]
        ];

        $pdfHtml = $this->makePartial('invoice_pdf', $data);

        if ($isPreview) {
            return \Response::make($pdfHtml);
        }

        try {
            $pdf = PDF::loadHtml($pdfHtml);
            $filename = $invoice->invoice_number . '.pdf';
            $pdfContent = $pdf->output();

            $file = (new File)->fromData($pdfContent, $filename);
            $invoice->pdf_file = $file;
            $invoice->save(); // Guarda el modelo de factura para persistir el cambio de relación y el archivo.

            // Verificar si el archivo se adjuntó correctamente
            if (!$invoice->pdf_file || !$invoice->pdf_file->exists) {
                throw new Exception("Failed to attach PDF file to invoice or file not found after attachment.");
            }

            // Mostrar el mensaje Flash PRIMERO
            Flash::success('Invoice PDF generated and attached successfully! Opening PDF in new tab...');

            // Redirigir a la URL pública del archivo PDF adjunto.
            // Esto abrirá el PDF en una nueva pestaña/ventana del navegador.
            $publicPdfUrl = $invoice->pdf_file->getUrl();

            return ['X_OCTOBER_REDIRECT' => $publicPdfUrl];
        } catch (Exception $e) {
            Log::error('Error generating or attaching PDF for Invoice #' . $invoice->id . ': ' . $e->getMessage());
            Flash::error('Error generating PDF: ' . $e->getMessage());
            return;
        }
    }

    public function onChangeServices()
    {
        // Log::info("onChangeServices CALLED."); // Uncomment for debugging

        // Get the current Invoice model instance from the FormController context.
        $invoice = $this->formGetModel();

        if (!$invoice) {
            // Log::warning('onChangeServices: Invoice model not found.'); // Uncomment for debugging
            return []; // Return empty if the invoice model cannot be found.
        }

        // --- Debugging: Log the services and their pivot data used for calculation ---
        // Log::info('onChangeServices: Services loaded for calculation AFTER DEFERRED BINDING APPLY:');
        // if ($invoice->services->isEmpty()) {
        //     Log::info('  No services found for invoice ID: ' . $invoice->id);
        // } else {
        //     foreach ($invoice->services as $service) {
        //         Log::info(sprintf(
        //             '  Service ID: %d, Name: %s, Pivot Price: %s, Pivot Quantity: %s',
        //             $service->id,
        //             $service->name,
        //             $service->pivot->price ?? 'N/A', // This should now reflect the edited value (e.g., 200.00)!
        //             $service->pivot->quantity ?? 'N/A' // This should also be updated!
        //         ));
        //     }
        // }
        // --- End Debugging ---

        // Calculate the invoice totals (subtotal, tax, total) based on the updated services.
        $invoice->calculateTotals();

        // Persist the newly calculated totals to the database.
        $invoice->save();

        // Log::info("onChangeServices: Totals after calculation and save (model instance): Subtotal={$invoice->subtotal}, Tax={$invoice->tax}, Total={$invoice->total}");

        // Return the updated total values as a JSON response. The frontend JavaScript (invoice-form-updates.js) will receive these values and use them to directly update the form fields in the UI.
        return [
            'subtotalValue' => number_format($invoice->subtotal, 2, '.', ''),
            'taxValue'      => number_format($invoice->tax, 2, '.', ''),
            'totalValue'    => number_format($invoice->total, 2, '.', ''),
        ];
    }
}
