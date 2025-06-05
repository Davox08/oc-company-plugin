<?php

namespace Davox\Company\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

/**
 * Services Controller
 * Manages services offered by the company within the backend.
 * Implements standard list and form behaviors for CRUD operations.
 */
class Services extends Controller
{
    /**
     * @var array Behaviors implemented by this controller.
     * - ListController: Provides list management functionality.
     * - FormController: Provides form creation and update functionality.
     */
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class
    ];

    /**
     * @var string Configuration file for the ListController behavior.
     * Defines columns and settings for the service list view.
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var string Configuration file for the FormController behavior.
     * Defines fields and settings for the service create/update form.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var array Permissions required to access the service management section.
     * Users must have 'davox.company.access_services' permission.
     */
    public $requiredPermissions = ['davox.company.access_services'];

    /**
     * Constructor.
     * Sets up the backend menu context for this controller.
     */
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Davox.Company', 'company', 'services');
    }
}