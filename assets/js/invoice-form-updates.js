$(document).ready(function() {
    var $invoiceForm = $('#layout-body form');

    if ($invoiceForm.length) {
        $invoiceForm.on('ajaxSuccess', function(event, context, data, textStatus, jqXHR) {

            if (context && data && data.subtotalValue !== undefined) {
                // console.log('AJAX Success detected for invoice form. Updating totals.', data);
                $('#Form-field-Invoice-subtotal')
                    .val(data.subtotalValue)
                    .text('');
                $('#Form-field-Invoice-tax')
                    .val(data.taxValue)
                    .text('');
                $('#Form-field-Invoice-total')
                    .val(data.totalValue)
                    .text('');

                // Puedes añadir un log en el navegador para confirmar
                // console.log('Subtotal updated to:', $('#Form-field-Invoice-subtotal').val());
            }
        });
    }
});