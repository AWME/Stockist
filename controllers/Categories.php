<?php namespace AWME\Stockist\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

use AWME\Stockist\Models\Category;

class Categories extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\ReorderController',
        'Backend\Behaviors\ImportExportController'
    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';
    public $importExportConfig = 'config_import_export.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('AWME.Stockist', 'stockist', 'stockist-categories');
    }

    public function test()
    {
        $test = Category::find(526)->products;

        return json_encode($test);
    }
}