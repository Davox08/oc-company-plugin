<?php

namespace Davox\Company\Models;

use Model;
use Carbon\Carbon;
use Davox\Company\Models\Setting;
use Illuminate\Support\Facades\Log;
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
        if (empty($this->invoice_number)) {
            $this->invoice_number = $this->generateInvoiceNumber();
        }
    }

    /**
     * Before update
     * @link https://docs.octobercms.com/3.x/extend/system/models.html#model-events
     * Update the invoice number
     */
    public function beforeUpdate()
    {
        if ($this->isDirty('issue_date') && !empty($this->invoice_number) && $this->issue_date instanceof \DateTimeInterface) {
            $newNumber = $this->updateInvoiceNumberDate($this->issue_date);
            if ($newNumber) {
                $this->invoice_number = $newNumber;
            }
        }
    }

    /**
     * Generate the invoice number
     * @return string
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV');

        $dateToFormat = $this->issue_date ?? Carbon::now();
        if (!$dateToFormat instanceof \DateTimeInterface) {
            $dateToFormat = Carbon::parse($dateToFormat);
        }
        $datePart = $dateToFormat->format('Ymd');

        $lastNumber = self::withTrashed()->count() + 1;

        $invoiceParts = [];
        if (!empty($prefix)) {
            $invoiceParts[] = trim($prefix);
        }
        $invoiceParts[] = $datePart;
        $invoiceParts[] = (string)$lastNumber;

        return implode('-', $invoiceParts);
    }

    /**
     * Actualiza solo la porción de la fecha de un número de factura existente.
     * Maneja números de factura con o sin prefijo.
     *
     * @param \DateTimeInterface $newIssueDate La nueva fecha de emisión.
     * @return string|null El nuevo número de factura con la fecha actualizada, o null si el formato es incorrecto.
     */
    protected function updateInvoiceNumberDate(\DateTimeInterface $newIssueDate): ?string
    {
        if (empty($this->invoice_number)) {
            Log::debug("UpdateInvoiceNumberDate: El número de factura actual está vacío. No se puede actualizar la fecha.");
            return null;
        }

        $parts = explode('-', $this->invoice_number);
        $numParts = count($parts);

        // Necesitamos al menos 2 partes (Fecha-Numero) o 3+ partes (Prefijo-Fecha-Numero o PrefijoConGuiones-Fecha-Numero)
        if ($numParts < 2) {
            Log::warning("UpdateInvoiceNumberDate: El número de factura '{$this->invoice_number}' tiene muy pocas partes ({$numParts}) para actualizar la fecha. Se esperaban al menos 2 (Fecha-Numero).");
            return null;
        }

        // La última parte siempre es el número incremental.
        $incrementalNumber = $parts[$numParts - 1];
        // La penúltima parte se asume que es la fecha.
        $oldDatePart = $parts[$numParts - 2];

        // Validación básica de las partes asumidas
        if (!ctype_digit($incrementalNumber)) {
            Log::warning("UpdateInvoiceNumberDate: La parte incremental '{$incrementalNumber}' del número de factura '{$this->invoice_number}' no es numérica.");
            return null;
        }
        // Puedes añadir una validación más estricta para oldDatePart si es necesario,
        // por ejemplo, strlen($oldDatePart) === 8 && ctype_digit($oldDatePart)
        // pero como solo la vamos a reemplazar, nos enfocamos en la estructura.

        // Todas las partes antes de las últimas dos (fecha y número) forman el prefijo (si existe).
        $prefixSegments = array_slice($parts, 0, $numParts - 2);

        $newDateFormatted = $newIssueDate->format('Ymd');

        $newInvoiceParts = [];

        if (!empty($prefixSegments)) {
            $prefix = implode('-', $prefixSegments);
            // Solo añadir el prefijo si, después de reconstruirlo, no es una cadena vacía.
            // Esto maneja correctamente el caso de un número original como "-FECHA-NUMERO" (prefijo vacío).
            if (trim($prefix) !== "") {
                $newInvoiceParts[] = trim($prefix);
            }
        }

        $newInvoiceParts[] = $newDateFormatted;
        $newInvoiceParts[] = $incrementalNumber;

        return implode('-', $newInvoiceParts);
    }

    /**
     * Calculate the totals for the invoice.
     */
    public function calculateTotals()
    {
        $subtotal = $this->services->sum(function ($service) {
            $price = $service->pivot->price ?? 0;
            $quantity = $service->pivot->quantity ?? 0;
            return $price * $quantity;
        });
        $this->subtotal = round($subtotal, 2);

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
