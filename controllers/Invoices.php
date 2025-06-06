<?php

namespace Davox\Company\Controllers;

use PDF;
use Log;
use Flash;
use Backend;
use Redirect;
use Exception;
use BackendMenu;
use System\Models\File;
use ApplicationException;
use Backend\Classes\Controller;
use Illuminate\Support\Facades\Url;
use Illuminate\Support\Facades\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Davox\Company\Models\Invoice;
use Davox\Company\Models\Setting;

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
        $this->addJs("/plugins/davox/company/assets/js/invoice-form-handler.js", "Davox.Company");
    }

    /**
     * Handles the AJAX request to export the current invoice to PDF.
     * Generates a new PDF, explicitly deletes any previously attached PDF for this invoice,
     * attaches the new PDF, saves the invoice, and then triggers a download of the PDF.
     * If 'preview' is in the input, it returns the HTML for the PDF.
     *
     * @return \Illuminate\Http\Response|array|void Can return a download response,
     * an HTML response for preview, or void on error (Flash message is set).
     */
    public function onExportPdf()
    {
        $isPreview = request()->boolean('preview');

        $invoice = $this->formGetModel();

        if (!$invoice || !$invoice->id) {
            Flash::error(trans('Invoice not found or cannot be exported!'));
            return;
        }

        $invoice->load('client', 'services');
        $companySettings = Setting::instance();

        if (!$companySettings) {
            Flash::error(trans('Company settings not found! Please configure them.'));
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
            return Response::make($pdfHtml);
        }

        try {
            $oldPdfFile = $invoice->pdf_file ?? null;

            $pdf = PDF::loadHtml($pdfHtml);

            $baseFilename = !empty($invoice->invoice_number) ? $invoice->invoice_number : ('invoice_' . $invoice->id);
            $safeFilename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $baseFilename);
            $filename = $safeFilename . '.pdf';

            $pdfContent = $pdf->output();

            $newPdfSystemFile = (new File)->fromData($pdfContent, $filename);

            $invoice->pdf_file = $newPdfSystemFile;
            $invoice->save();

            if ($oldPdfFile) {
                $currentPdfFileAfterSave = $invoice->fresh()->pdf_file;
                if ($currentPdfFileAfterSave && $oldPdfFile->id === $currentPdfFileAfterSave->id) {
                } else {
                    try {
                        $oldPdfFile->delete();
                        Log::info("[Invoices::onExportPdf] Old PDF (ID: {$oldPdfFile->id}) explicitly deleted.");
                    } catch (Exception $deleteException) {
                        Log::error("[Invoices::onExportPdf] Could not explicitly delete old PDF (ID: {$oldPdfFile->id}): " . $deleteException->getMessage());
                    }
                }
            }

            if (!$invoice->pdf_file || !$invoice->pdf_file->id) {
                throw new ApplicationException('PDF file not available for download after generation.');
            }

            $downloadUrl = Url::route('davox.company.invoices.downloadGeneratedPdf', [
                'invoice_id' => $invoice->id,
                'file_id'    => $invoice->pdf_file->id
            ]);

            Flash::success('PDF generado y adjuntado. La descarga comenzará en breve.');

            return [
                'downloadUrl' => $downloadUrl,
            ];
        } catch (Exception $e) {
            Log::error("[Invoices::onExportPdf] Error in onExportPdf for Invoice ID {$invoice->id}: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            Flash::error(trans('Error generating PDF') . ': ' . $e->getMessage());
            throw new ApplicationException(trans('Error generating PDF') . ': ' . $e->getMessage());
        }
    }

    /**
     * Action method to serve a specific generated PDF for download.
     * This method is called directly by the browser via a URL.
     *
     * @param int $invoiceId The ID of the Invoice model.
     * @param int $fileId The ID of the System\Models\File record to be downloaded.
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadGeneratedPdf($invoiceId = null, $fileId = null)
    {
        try {
            // findOrFail lanzará ModelNotFoundException si no se encuentra, que se captura abajo.
            $invoice = Invoice::findOrFail((int)$invoiceId);

            // Validar que el fileId es el que está actualmente adjunto a la factura.
            if (!$invoice->pdf_file || $invoice->pdf_file->id != (int)$fileId) {
                Log::warning("[Invoices::downloadGeneratedPdf] File ID {$fileId} no coincide con el PDF actual para Factura ID {$invoiceId}. Current PDF File ID: " . ($invoice->pdf_file->id ?? 'None'));
                Flash::error(trans('davox.company::lang.flash.pdf_download_invalid_file', 'El archivo PDF solicitado no es válido para esta factura.'));
                return Redirect::to(Backend::url('davox/company/invoices/update/' . $invoice->id));
            }

            // Usar el método download() del modelo File
            Log::info("[Invoices::downloadGeneratedPdf] Sirviendo para descarga el archivo: {$invoice->pdf_file->file_name} (File ID: {$invoice->pdf_file->id}) para Factura ID {$invoiceId}");

            // El método download() del modelo File ya devuelve una Symfony\Component\HttpFoundation\StreamedResponse
            // o similar, configurada para la descarga.
            return $invoice->pdf_file->download();
        } catch (ModelNotFoundException $e) {
            Log::error("[Invoices::downloadGeneratedPdf] Factura no encontrada (ID: {$invoiceId}): " . $e->getMessage());
            Flash::error(trans('davox.company::lang.flash.invoice_not_found_download', 'Factura no encontrada para la descarga.'));
            return Redirect::to(Backend::url('davox/company/invoices'));
        } catch (Exception $e) {
            Log::error("[Invoices::downloadGeneratedPdf] Error descargando PDF (InvoiceID: {$invoiceId}, FileID: {$fileId}): " . $e->getMessage());
            Flash::error(trans('davox.company::lang.flash.pdf_download_error', 'Error al intentar descargar el PDF.') . ': ' . $e->getMessage());
            // Redirigir a la lista de facturas en caso de un error genérico.
            return Redirect::to(Backend::url('davox/company/invoices'));
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
