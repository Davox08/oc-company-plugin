<?php
// Recoge todos los mensajes flash de la sesión.
// Flash::all() los recupera y luego los elimina de la sesión.
$allFlashMessages = Flash::all();
?>

<?php if (!empty($allFlashMessages)): ?>
    <?php foreach ($allFlashMessages as $type => $messagesCollection): ?>
        <?php
        // $messagesCollection DEBERÍA ser un array de strings (mensajes) para este $type.
        // Para proteger contra el caso donde sea un solo string (causa del error),
        // lo convertimos explícitamente a un array.
        ?>
        <?php foreach ((array) $messagesCollection as $message): ?>
            <p class="flash-message static <?= $type === 'error' ? 'danger' : e($type) // 'danger' es la clase de Bootstrap para errores
                                            ?> oc-flash-message"
                data-control="flash-message"
                data-message-text="<?= e(str_replace(["\r", "\n"], '', $message)) // Limpiar saltos de línea para el atributo de datos
                                    ?>"
                data-message-type="<?= $type === 'error' ? 'danger' : e($type) ?>"
                data-message-duration="5"> <?= e($message) // Muestra el texto del mensaje directamente
                                            ?>
            </p>
        <?php endforeach ?>
    <?php endforeach ?>
<?php endif ?>