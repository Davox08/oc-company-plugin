<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values. It is possible to add all defines that can be set
    | in dompdf_config.inc.php. You can also override the entire config file.
    |
    */
    'show_warnings' => true,   // Throw an Exception on warnings from dompdf

    'public_path' => public_path(),  // Correct for OctoberCMS/Laravel context

    /*
     * Dejavu Sans font is missing glyphs for converted entities, turn it off if you need to show € and £.
     */
    'convert_entities' => true,

    'options' => [
        /**
         * The location of the DOMPDF font directory
         * DEBE EXISTIR Y SER ESCRIBIBLE POR EL SERVIDOR WEB.
         */
        'font_dir' => storage_path('app/davox/company/fonts'), // OctoberCMS aligned path

        /**
         * The location of the DOMPDF font cache directory
         * DEBE EXISTIR Y SER ESCRIBIBLE POR EL SERVIDOR WEB.
         */
        'font_cache' => storage_path('app/davox/company/font_cache'), // OctoberCMS aligned path (o igual a font_dir)

        /**
         * The location of a temporary directory.
         * DEBE SER ESCRIBIBLE POR EL SERVIDOR WEB.
         */
        'temp_dir' => storage_path('temp/dompdf'), // OctoberCMS aligned path

        /**
         * ==== IMPORTANT ====
         *
         * dompdf's "chroot": Prevents dompdf from accessing system files or other
         * files on the webserver.
         */
        'chroot' => realpath(base_path()), // Correct and crucial for OctoberCMS security

        /**
         * Protocol whitelist
         */
        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []], // Consider security implications if chroot is bypassed
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        /**
         * Operational artifact (log files, temporary files) path validation
         */
        'artifactPathValidation' => null, // Consider reviewing this if strict path validation is needed

        /**
         * @var string
         * Archivo de log para DOMPDF
         */
        'log_output_file' => storage_path('logs/dompdf.log'), // OctoberCMS aligned path

        /**
         * Whether to enable font subsetting or not.
         */
        'enable_font_subsetting' => false,

        /**
         * The PDF rendering backend to use
         */
        'pdf_backend' => 'CPDF', // CPDF es el default y no requiere extensiones adicionales

        /**
         * html target media view which should be rendered into pdf.
         */
        'default_media_type' => 'screen',

        /**
         * The default paper size.
         */
        'default_paper_size' => 'a4',

        /**
         * The default paper orientation.
         */
        'default_paper_orientation' => 'portrait',

        /**
         * The default font family
         */
        'default_font' => 'serif',

        /**
         * Image DPI setting
         */
        'dpi' => 96,

        /**
         * Enable embedded PHP - ¡RIESGO DE SEGURIDAD SI NO SE CONTROLA LA FUENTE HTML!
         */
        'enable_php' => false, // Recomendado: false a menos que sea absolutamente necesario y confíes en el HTML

        /**
         * Enable inline JavaScript
         */
        'enable_javascript' => true, // JavaScript interpretado por el visor de PDF, no por el navegador

        /**
         * Enable remote file access - ¡RIESGO DE SEGURIDAD SI NO SE CONTROLA LA FUENTE HTML!
         */
        'enable_remote' => true, // Permite cargar imágenes/CSS remotos. Asegúrate de que sea necesario.

        /**
         * List of allowed remote hosts
         */
        'allowed_remote_hosts' => null, // NULL para permitir cualquier host si enable_remote es true. Restringir si es posible.

        /**
         * A ratio applied to the fonts height to be more like browsers' line height
         */
        'font_height_ratio' => 1.1,

        /**
         * Use the HTML5 Lib parser
         */
        'enable_html5_parser' => true, // Ya es el default en dompdf 2.x
    ],

];
