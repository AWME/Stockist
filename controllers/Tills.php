<?php namespace AWME\Stockist\Controllers;

use Flash;
use Redirect;
use Backend;
use Request;
use BackendMenu;
use BackendAuth;
use Backend\Models\User;
use Backend\Classes\Controller;
use ValidationException;

use AWME\Stockist\Models\Till;
use AWME\Stockist\Models\Settings;

class Tills extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'awme.stockist.read_tills' 
    ];

    public $bodyClass = 'compact-container';
    protected $assetsPath = '/plugins/awme/stockist/assets';

    public function __construct()
    {
        if (post('report_mode'))
            $this->formConfig = 'config_report_form.yaml';

        parent::__construct();
        BackendMenu::setContext('AWME.Stockist', 'stockist', 'stockist-tills');

        $this->addCss($this->assetsPath.'/css/modal-form.css');
        $this->addJs($this->assetsPath.'/js/product-form.js');
        $this->addJs($this->assetsPath.'/js/print-this.js');
    }

    public function onCreate()
    {
        $this->asExtension('FormController')->create_onSave();
        
        if(Request::input('Till.operation') == 'till_report'){

            $Till = Till::where('seller_id', BackendAuth::getUser()->id)
                            ->where('operation','till_report')->orderBy('id','desc')->first();

            return Redirect::to(Backend::url('awme/stockist/tills/preview/'.$Till->id));
        }else return $this->listRefresh('tills');
    }

    public function onCreateForm()
    {
        $this->asExtension('FormController')->create();
        return $this->makePartial('create_till_form');
    }

    public function update($recordId = null, $context = null)
    {
        return Redirect::to(Backend::url('awme/stockist/tills'));
    }

    /**
     * Update
     */
    public function preview($recordId = null, $context = null)
    {

        $Till = Till::find($recordId);
        if($Till->operation != 'till_report')
        {
            Flash::error(trans('Failed to view report'));
            return Redirect::to(Backend::url('awme/stockist/tills'));
        }


        $this->vars['options']['show_seller'] = Settings::get('invoice_show_seller');
        $this->vars['options']['allow_print'] = Settings::get('use_print');
        $this->vars['options']['company_name'] = Settings::get('company_name');
        $this->vars['options']['company_logo'] = Settings::get('company_logo');
        $this->vars['options']['company_slogan'] = Settings::get('company_slogan');
        $this->vars['options']['company_address'] = Settings::get('company_address');
        $this->vars['options']['company_phone'] = Settings::get('company_phone');
        $this->vars['options']['thank_you_message'] = Settings::get('thank_you_message');
        $this->vars['options']['bottom_left_message'] = Settings::get('bottom_left_message');
        $this->vars['options']['not_valid_message'] = Settings::get('not_valid_message');
        $this->vars['options']['bottom_right_message'] = Settings::get('bottom_right_message');

        $Till = Till::find($recordId);
        
        
        if($Till->concept == 'daily_report' && $Till->record_data['report_date'])
        {
            $from = $Till->record_data['report_date'];
            $to = $Till->record_data['report_date'];
        }else{
            $from = $Till->record_data['report_date_from'];
            $to = $Till->record_data['report_date_to'];
        }
            $sellers = $Till->record_data['customers'];

        $table = $Till->operations()->bySeller($sellers)->atDate($from, $to)->get();
        $this->vars['table'] = $table;
        $this->vars['profit'] = $Till->operations()->bySeller($sellers)->atDate($from, $to)->getTotal('deposit');
        $this->vars['expenses'] = $Till->operations()->bySeller($sellers)->atDate($from, $to)->getTotal('withdraw');
        $this->vars['sellers'] = $Till->operations()->bySeller($sellers)->atDate($from, $to)->getSellers()->get();
        #$this->vars['sellers'] = User::whereIn('id',$sellers)->get();

        $this->asExtension('FormController')->update($recordId, $context);
    }

    public function onTillReport()
    {
        // Call the ListController behavior index() method
        $this->asExtension('FormController')->create();
        return $this->makePartial('create_till_report_form');
    }
}