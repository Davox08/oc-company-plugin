<?php

namespace Davox\Company\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Clients extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\FormController::class
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['davox.company.access_clients'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Davox.Company', 'company', 'clients');
    }
}
