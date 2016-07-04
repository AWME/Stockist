<?php namespace AWME\Stockist\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Tills extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'awme.stockist.read_tills' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('AWME.Stockist', 'stockist', 'stockist-tills');
    }
}