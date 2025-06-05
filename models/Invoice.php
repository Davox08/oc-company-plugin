<?php

namespace Davox\Company\Models;

use Model;
use Carbon\Carbon;
use Davox\Company\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Invoice Model.
 * Represents an invoice in the system, managing its data, relations, and business logic
 * such as number generation and totals calculation.
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Invoice extends Model
{
    /**
     * @var string The database table used by the model.
     */
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\SortableRelation;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'davox_company_invoices';

    /**
     * @var array Relations that should touch the parent's timestamps.
     */
    protected $touches = ['client', 'services'];

    /**
     * @var array Attributes that should be mutated to dates.
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'issue_date'
    ];

    /**
     * @var array Validation rules for the model.
     */
    public $rules = [
        'issue_date' => 'required|date',
    ];

    /*
     * Relations
     */
    public $belongsTo = [
        'client' => [
            \Davox\Company\Models\Client::class,
            'key' => 'client_id',
        ]
    ];

    public $belongsToMany = [
        'services' => [
            \Davox\Company\Models\Service::class,
            'table'         => 'davox_company_invoice_service',
            'key'           => 'invoice_id',
            'otherKey'      => 'service_id',
            'pivot'         => ['price', 'quantity', 'description'],
            'pivotSortable' => 'sort_order',
            'timestamps'    => true,
        ]
    ];

    public $attachOne = [
        'pdf_file' => [\System\Models\File::class]
    ];

    /**
     * Event handler for before the model is created.
     * Assigns an invoice number if it's not already set.
     *
     * @return void
     */
    public function beforeCreate(): void
    {
        if (empty($this->invoice_number)) {
            $this->invoice_number = $this->generateInvoiceNumber();
        }
    }

    /**
     * Event handler for before the model is updated.
     * Updates the invoice number's date part if the issue_date has changed.
     *
     * @return void
     */
    public function beforeUpdate(): void
    {
        // Only update invoice number if issue_date is dirty, an invoice_number already exists,
        // and issue_date is a valid DateTimeInterface.
        if ($this->isDirty('issue_date') && !empty($this->invoice_number) && $this->issue_date instanceof \DateTimeInterface) {
            $newNumber = $this->updateInvoiceNumberDate($this->issue_date);
            if (!is_null($newNumber)) { // Check if updateInvoiceNumberDate returned a valid number
                $this->invoice_number = $newNumber;
            }
        }

        if ($this->isDirty('services')) {
            $this->processExistingServicesAndRecalculateTotals();
        }
    }

    /**
     * Generates a new invoice number.
     * The number consists of an optional prefix, a date part (Ymd), and an incremental number.
     * The prefix is retrieved from settings, defaulting to 'INV'.
     *
     * @return string The generated invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV');

        $dateToFormat = $this->issue_date ?? Carbon::now();
        if (!$dateToFormat instanceof \DateTimeInterface) {
            // Attempt to parse if it's a string or other parsable format
            try {
                $dateToFormat = Carbon::parse($dateToFormat);
            } catch (\Exception $e) {
                Log::warning("[Invoice::generateInvoiceNumber] Could not parse issue_date, using current date. Error: " . $e->getMessage(), ['issue_date_value' => $this->issue_date]);
                $dateToFormat = Carbon::now();
            }
        }
        $datePart = $dateToFormat->format('Ymd');

        // Get the next available number
        $lastNumber = self::withTrashed()->count() + 1;

        $invoiceParts = [];
        if (!empty(trim((string) $prefix))) { // Ensure prefix is not just whitespace
            $invoiceParts[] = trim((string) $prefix);
        }
        $invoiceParts[] = $datePart;
        $invoiceParts[] = (string)$lastNumber;

        return implode('-', $invoiceParts);
    }

    /**
     * Updates the date part of an existing invoice number.
     * This method robustly handles invoice numbers that may or may not have a prefix.
     *
     * @param \DateTimeInterface $newIssueDate The new issue date to incorporate into the invoice number.
     * @return string|null The updated invoice number string, or null if the original number format is invalid.
     */
    protected function updateInvoiceNumberDate(\DateTimeInterface $newIssueDate): ?string
    {
        if (empty($this->invoice_number)) {
            // Log::debug("[Invoice::updateInvoiceNumberDate] Current invoice_number is empty. Cannot update date part."); // Developmental log
            return null;
        }

        $parts = explode('-', $this->invoice_number);
        $numParts = count($parts);

        if ($numParts < 2) {
            Log::warning("[Invoice::updateInvoiceNumberDate] Invoice number '{$this->invoice_number}' has too few parts ({$numParts}) to update the date. Expected at least 2 (Date-Number).");
            return null;
        }

        $incrementalNumber = $parts[$numParts - 1];
        // $oldDatePart = $parts[$numParts - 2]; // Not strictly needed for reconstruction

        if (!ctype_digit((string) $incrementalNumber)) { // Cast to string in case it's numeric but not string type
            Log::warning("[Invoice::updateInvoiceNumberDate] Incremental number part '{$incrementalNumber}' of invoice number '{$this->invoice_number}' is not purely numeric.");
            return null;
        }

        $prefixSegments = array_slice($parts, 0, $numParts - 2);
        $newDateFormatted = $newIssueDate->format('Ymd');
        $newInvoiceParts = [];

        if (!empty($prefixSegments)) {
            $prefix = implode('-', $prefixSegments);
            if (trim($prefix) !== "") {
                $newInvoiceParts[] = trim($prefix);
            }
        }

        $newInvoiceParts[] = $newDateFormatted;
        $newInvoiceParts[] = $incrementalNumber;

        return implode('-', $newInvoiceParts);
    }

    /**
     * Calculates the subtotal, tax, and total for the invoice based on its services.
     * The results are rounded to 2 decimal places and set on the model's attributes.
     * This method expects the 'services' relation to be loaded with pivot data (price, quantity).
     *
     * @return void
     */
    public function calculateTotals(): void
    {
        // Log::info('--- [Invoice::calculateTotals] METHOD CALLED --- For Invoice ID: ' . ($this->id ?? 'NEW')); // Developmental log

        $rawSubtotal = 0.0;

        if ($this->relationLoaded('services') && $this->services !== null && $this->services->count() > 0) {
            // Log::info('[Invoice::calculateTotals] Services relation loaded, items: ' . $this->services->count()); // Developmental log
            $rawSubtotal = $this->services->sum(function ($service) {
                if (is_null($service->pivot)) {
                    Log::warning('[Invoice::calculateTotals] Service ID ' . ($service->id ?? 'unknown') . ' is missing pivot data.');
                    return 0;
                }
                $priceOnPivot = $service->pivot->price ?? 0;
                $quantityOnPivot = $service->pivot->quantity ?? 0;

                $numericPrice = is_numeric($priceOnPivot) ? (float)$priceOnPivot : 0.0;
                $numericQuantity = is_numeric($quantityOnPivot) ? (float)$quantityOnPivot : 0.0;

                return $numericPrice * $numericQuantity;
            });
            // Log::info('[Invoice::calculateTotals] Raw subtotal from services: ' . $rawSubtotal); // Developmental log
        } else {
            // Log::info('[Invoice::calculateTotals] Services relation not loaded, null, or empty. Subtotal will be 0.'); // Developmental log
        }

        $this->subtotal = round($rawSubtotal, 2);

        $taxFactor = Setting::getTaxFactor(); // Assumes Setting::getTaxFactor() is robust and returns a float factor
        // Log::info('[Invoice::calculateTotals] Tax Factor from Setting: ' . $taxFactor); // Developmental log

        $this->tax = round($this->subtotal * $taxFactor, 2);
        $this->total = round($this->subtotal + $this->tax, 2);

        // Log::info("[Invoice::calculateTotals] COMPLETED - Subtotal: {$this->subtotal}, Tax: {$this->tax}, Total: {$this->total}"); // Developmental log
    }

    /**
     * Reloads the 'services' relation for an existing invoice from the database
     * and then recalculates all invoice totals.
     * This method is primarily intended for use in an update context,
     * for example, after services (line items) have been modified.
     *
     * @return void
     */
    public function processExistingServicesAndRecalculateTotals(): void
    {
        if (!$this->exists) {
            Log::warning("[Invoice::processExistingServicesAndRecalculateTotals] Method called on a new (non-existent) invoice. Action aborted. Totals may be incorrect if services were expected from POST data not passed to this method.");
            // For a new invoice, totals should ideally be calculated after services are set from form data.
            // Setting to zero to prevent inconsistent state if called inappropriately.
            $this->subtotal = 0.0;
            $this->tax = 0.0;
            $this->total = 0.0;
            return;
        }

        // Log::info("[Invoice::processExistingServicesAndRecalculateTotals] Initiating for Invoice ID: " . $this->id); // Developmental log

        // Reload the 'services' relation from the DB to ensure fresh data.
        $this->load('services');

        // Developmental log to verify services after reload
        // Log::info('[Invoice::processExistingServicesAndRecalculateTotals] Services AFTER reload and BEFORE calculateTotals:');
        // if ($this->relationLoaded('services') && $this->services !== null && !$this->services->isEmpty()) {
        //     Log::info('  Number of services loaded: ' . $this->services->count());
        //     foreach ($this->services as $s) {
        //         $pivotQuantity = $s->pivot->quantity ?? 'PivotQty N/A';
        //         $pivotPrice = $s->pivot->price ?? 'PivotPrice N/A';
        //         Log::info(sprintf('  (Model) Service ID: %s, Name: %s, Pivot Qty: %s, Pivot Price: %s', $s->id ?? 'N/A', $s->name ?? 'N/A', $pivotQuantity, $pivotPrice));
        //     }
        // } else {
        //     Log::info('  [Invoice::processExistingServicesAndRecalculateTotals] No services in relation or collection is empty after reload.');
        // }

        // Call the existing function to calculate totals
        $this->calculateTotals(); // calculateTotals uses $this->services
        // Log::info("[Invoice::processExistingServicesAndRecalculateTotals] Process completed. Calculated Totals: Subtotal={$this->subtotal}, Tax={$this->tax}, Total={$this->total}"); // Developmental log
    }
}