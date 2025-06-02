<?php

namespace Davox\Company\Models;

use Model;
use Illuminate\Support\Facades\Log;

use Davox\Company\Models\Setting;

/**
 * Application Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Invoice extends Model
{
    /**
     * Traits
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#validation
     *
     */
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\SortableRelation;

    /**
     * Defining model
     * @link https://docs.octobercms.com/3.x/extend/settings/model-settings.html#model-class-definition
     *
     */
    protected $table = 'davox_company_invoices';

    /**
     * Touching Parent Timestamps
     * @link https://docs.octobercms.com/3.x/extend/database/relations.html#touching-parent-timestamps
     *
     */
    protected $touches = ['client', 'services'];

    /**
     * Dates
     * @link https://docs.octobercms.com/3.x/extend/system/models.html#supported-properties
     *
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'issue_date'
    ];

    /**
     * Validation rules
     * @link https://docs.octobercms.com/3.x/extend/database/traits.html#validation
     * @var array $rules
     */
    public $rules = [
        'issue_date' => 'required|date',
    ];

    /**
     * Relations - One to many
     * @link https://docs.octobercms.com/3.x/extend/database/relations.html#one-to-many
     * Invoice belongs to client
     */
    public $belongsTo = [
        'client' => [
            \Davox\Company\Models\Client::class,
            'table' => 'davox_company_clients',
        ]
    ];

    /**
     * Relations - Many to many
     * @link https://docs.octobercms.com/3.x/extend/database/relations.html#many-to-many
     * Invoices belongs to many services
     * Pivot table: davox_company_invoice_service
     * Pivot fields: price, quantity, description
     */
    public $belongsToMany = [
        'services' => [
            \Davox\Company\Models\Service::class,
            'table'         => 'davox_company_invoice_service',
            'pivot'         => ['price', 'quantity', 'description'],
            'pivotSortable' => 'sort_order'
        ]
    ];
    /**
     * File Attachments
     * @link https://docs.octobercms.com/3.x/extend/database/attachments.html
     */
    public $attachOne = [
        'pdf_file' => ['System\Models\File']
    ];

    /**
     * Before create
     * @link https://docs.octobercms.com/3.x/extend/system/models.html#model-events
     * Assign the invoice number
     */
    public function beforeCreate()
    {
        $this->invoice_number = $this->generateInvoiceNumber();
    }

    /**
     * Before update
     * @link https://docs.octobercms.com/3.x/extend/system/models.html#model-events
     * Update the invoice number
     */
    public function beforeUpdate()
    {
        $this->invoice_number = $this->updateInvoiceNumberDate($this->issue_date);
    }

    /**
     * Generate the invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        // Get the prefix from the settings
        $prefix = Setting::get('invoice_prefix', 'INV');
        // Get the date part of the invoice number
        $datePart = $this->issue_date ? $this->issue_date->format('Ymd') : date('Ymd');
        // Get the last number of the invoice
        $lastNumber = self::withTrashed()->count() + 1;
        // Format the invoice number
        $invoiceNumber = sprintf('%s-%s-%d', $prefix, $datePart, $lastNumber);
        // Save the invoice number
        return $invoiceNumber;
    }

    /**
     * Updates only the date portion of an existing invoice number.
     *
     * @param  $newIssueDate The new issue date.
     * @return string|null The new invoice number with the updated date, or null if the format is incorrect.
     */
    protected function updateInvoiceNumberDate($newIssueDate): ?string
    {
        if (empty($this->invoice_number)) {
            return null;
        }

        // Split the existing invoice number into its parts
        $parts = explode('-', $this->invoice_number);

        if (count($parts) !== 3) {
            // The invoice number format is not as expected
            \Log::warning("The invoice number '{$this->invoice_number}' is not in the expected format to update the date.");
            return null;
        }

        $prefix = $parts[0];
        // $oldDatePart = $parts[1];
        $lastNumber = $parts[2];

        // Format the new date part
        $newDatePart = $newIssueDate->format('Ymd');

        // Reconstruct the invoice number with the new date
        $updatedInvoiceNumber = sprintf('%s-%s-%s', $prefix, $newDatePart, $lastNumber);

        return $updatedInvoiceNumber;
    }

    /**
     * Calculate the totals for the invoice.
     */
    public function calculateTotals()
    {
        $subtotal = $this->services->sum(function ($service) {
            $price = $service->pivot->price ?? 0; // Default to 0 if null
            $quantity = $service->pivot->quantity ?? 0; // Default to 0 if null
            return $price * $quantity;
        });
        $this->subtotal = round($subtotal, 2); // Round subtotal to 2 decimal places

        $taxFactor = Setting::getTaxFactor();

        if (!is_numeric($taxFactor) || $taxFactor < 0) {
            Log::warning("Invalid or missing tax factor for invoice calculation. Using 0.", [
                'invoice_id' => $this->id,
                'retrieved_tax_factor' => $taxFactor
            ]);
            $taxFactor = 0;
        }

        // Calculate the tax on the already rounded subtotal
        $this->tax = round($this->subtotal * $taxFactor, 2);

        // The total is the sum of the subtotal and the tax (both already rounded)
        $this->total = round($this->subtotal + $this->tax, 2);
    }
}
