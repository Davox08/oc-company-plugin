$(document).ready(function() {
    // Intenta ser un poco más específico con el selector del formulario si es posible,
    // por ejemplo, si tu <form> tiene un ID o un data-attribute específico.
    // Por ahora, $('#layout-body form') es funcional si solo hay un formulario principal.
    var $invoiceForm = $('#layout-body form');

    if ($invoiceForm.length === 0) {
        // Opcional: log si el formulario no se encuentra, para depuración.
        console.warn('Invoice form not found on this page. JS interactions will not be initialized.');
        return; // Salir si no hay formulario de factura en la página
    }

    $invoiceForm.on('ajaxSuccess', function(event, context, data, textStatus, jqXHR) {
        // 'context' contiene información sobre la petición AJAX, incluyendo context.handler
        // 'data' es la respuesta del servidor (usualmente un objeto JSON o HTML)

        // --- Manejador para la respuesta de onChangeServices (actualización de totales) ---
        if (context && context.handler === 'onChangeServices') {
            if (data && data.X_DAVOX_COMPANY_TOTALS_UPDATED === true) {
                console.log('onChangeServices AJAX Success: Updating total fields.', data);

                // Actualizar campos de totales si los valores están presentes en la respuesta
                if (data.subtotalValue !== undefined) {
                    $('#Form-field-Invoice-subtotal').val(data.subtotalValue);
                }
                if (data.taxValue !== undefined) {
                    $('#Form-field-Invoice-tax').val(data.taxValue);
                }
                if (data.totalValue !== undefined) {
                    $('#Form-field-Invoice-total').val(data.totalValue);
                }
            } else if (data && data.error) { // Si tu handler onChangeServices devuelve un error JSON personalizado
                $.oc.flashMsg({ text: data.error, class: 'error', interval: 5 });
            }
        }

        // --- Manejador para la respuesta de onExportPdf (iniciar descarga de PDF) ---
        if (context && context.handler === 'onExportPdf') {
            if (data && data.downloadUrl) {
                console.log('onExportPdf AJAX Success: Initiating PDF download from URL:', data.downloadUrl);

                // Método robusto para iniciar la descarga
                var link = document.createElement('a');
                link.href = data.downloadUrl;
                // El nombre del archivo se establece por el servidor con Content-Disposition.
                // link.setAttribute('download', 'invoice.pdf'); // No es estrictamente necesario aquí.
                link.style.display = 'none'; // El enlace no necesita ser visible
                document.body.appendChild(link);
                link.click(); // Simula un clic en el enlace para iniciar la descarga
                document.body.removeChild(link); // Elimina el enlace temporal del DOM
                link.remove(); // Limpieza adicional

                // Los mensajes Flash::success de onExportPdf deberían mostrarse automáticamente
                // por el framework AJAX de October si la respuesta principal no es la descarga en sí.
                // Si 'data' también contiene un mensaje flash específico, puedes mostrarlo:
                // if (data.flash_message_text) {
                //     $.oc.flashMsg({ text: data.flash_message_text, class: data.flash_message_class || 'success', interval: 5 });
                // }
            } else if (data && data.error) { // Si onExportPdf devuelve un error JSON personalizado
                $.oc.flashMsg({ text: data.error, class: 'error', interval: 5 });
            }
            // Nota: Si onExportPdf lanza una ApplicationException, el framework AJAX de October la mostrará como un popup.
        }

        // Puedes añadir más bloques 'if (context.handler === 'onOtroHandler')' aquí para otras acciones.
    });

    // Opcional: Manejo de errores AJAX genéricos para el formulario, si no se manejan específicamente.
    // $invoiceForm.on('ajaxError', function(event, context, statusText, jqXHR, errorThrown) {
    //     console.error('Generic AJAX Error on invoice form:', {
    //         handler: context ? context.handler : 'N/A',
    //         status: statusText,
    //         error: errorThrown,
    //         response: jqXHR ? jqXHR.responseJSON : null
    //     });
    //     // Muestra un mensaje genérico si no fue un error manejado por October (como ApplicationException)
    //     if (!(jqXHR && jqXHR.responseJSON && (jqXHR.responseJSON.X_OCTOBER_ERROR_MESSAGE || jqXHR.responseJSON.error))) {
    //         $.oc.flashMsg({ text: 'An unexpected error occurred.', class: 'error' });
    //     }
    // });
});