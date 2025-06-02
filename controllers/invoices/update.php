<?php Block::put('breadcrumb') ?>
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= Backend::url('davox/company/invoices') ?>">Invoices</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($this->pageTitle) ?></li>
</ol>
<?php Block::endPut() ?>

<?php if (!$this->fatalError): ?>

    <?= Form::open(['class' => 'd-flex flex-column h-100']) ?>

    <div class="flex-grow-1">
        <?= $this->formRender() ?>
    </div>

    <div class="form-buttons">
        <div data-control="loader-container">
            <button
                type="submit"
                data-request="onSave"
                data-request-data="{ redirect: 0 }"
                data-hotkey="ctrl+s, cmd+s"
                data-request-message="<?= __("Saving :name...", ['name' => $formRecordName]) ?>"
                class="btn btn-primary">
                <?= __("Save") ?>
            </button>
            <button
                type="button"
                data-request="onSave"
                data-request-data="{ close: 1 }"
                data-browser-redirect-back
                data-hotkey="ctrl+enter, cmd+enter"
                data-request-message="<?= __("Saving :name...", ['name' => $formRecordName]) ?>"
                class="btn btn-default">
                <?= __("Save & Close") ?>
            </button>
            <button
                type="button"
                class="oc-icon-delete btn-icon danger pull-right"
                data-request="onDelete"
                data-request-message="<?= __("Deleting :name...", ['name' => $formRecordName]) ?>"
                data-request-confirm="<?= __("Delete this record?") ?>">
            </button>
            <button
                type="button"
                class="btn btn-default oc-icon-file-pdf-o"
                data-request="onExportPdf"
                data-load-indicator="Generating PDF..."
                data-request-redirect="0"
                data-request-flash>
                Export to PDF
            </button>

            <button
                type="button"
                class="btn btn-info oc-icon-code" {{-- Clase 'btn-info' para un color diferente, 'oc-icon-code' para el icono --}}
                data-request="onExportPdf" {{-- Llama al mismo método AJAX del controlador --}}
                data-load-indicator="Generating HTML Preview..." {{-- Mensaje mientras se procesa --}}
                data-request-data="preview: 1" {{-- ¡Pasa el parámetro 'preview' a tu controlador! --}}
                data-request-success="window.open('', '_blank').document.write(data); $.oc.flashMsg({text: 'HTML Preview generated!', class: 'success'});">
                Preview HTML
            </button>
            <span class="btn-text">
                <span class="button-separator"><?= __("or") ?></span>
                <a
                    href="<?= Backend::url('davox/company/invoices') ?>"
                    class="btn btn-link p-0">
                    <?= __("Cancel") ?>
                </a>
            </span>
        </div>
    </div>

    <?= Form::close() ?>

<?php else: ?>

    <p class="flash-message static error">
        <?= e($this->fatalError) ?>
    </p>
    <p>
        <a
            href="<?= Backend::url('davox/company/invoices') ?>"
            class="btn btn-default">
            <?= __("Return to List") ?>
        </a>
    </p>

<?php endif ?>