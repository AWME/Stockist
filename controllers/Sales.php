<?php namespace AWME\Stockist\Controllers;

use Flash;
use Request;
use Backend;
use Redirect;
use BackendMenu;
use ValidationException;
use Backend\Classes\Controller;

use AWME\Stockist\Models\Settings;
use AWME\Stockist\Models\Sale;
use AWME\Stockist\Models\Product;
use AWME\Stockist\Models\SaleProduct;
use AWME\Stockist\Models\SaleProductPivot;
use AWME\Stockist\Models\PayMethod;
use AWME\Stockist\Models\SalePayMethod;

class Sales extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\ReorderController',
        'Backend.Behaviors.RelationController'
    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';
    
    public $relationConfig = 'config_relation.yaml';

    public $requiredPermissions = [
        'awme.stockist.usage' 
    ];

    public $bodyClass = 'compact-container';
    protected $assetsPath = '/plugins/awme/stockist/assets';

    public function __construct()
    {
        parent::__construct();
        
        BackendMenu::setContext('AWME.Stockist', 'stockist', 'stockist-sales');

        $this->addCss($this->assetsPath.'/css/modal-form.css');
        $this->addJs($this->assetsPath.'/js/product-form.js');
        $this->addJs($this->assetsPath.'/js/print-this.js');
    }

    /**
     * Update
     */
    public function update($recordId = null, $context = null)
    {
        $Sale = Sale::find($recordId);

        if($this->isStatus($Sale->status, ['closed','canceled'])) 
            return Redirect::to(Backend::url('awme/stockist/sales/preview/'.$recordId));

        $this->vars['sale'] = $Sale;
        $this->vars['options']['use_taxes'] = Settings::get('use_taxes');
        $this->vars['options']['use_scanner'] = Settings::get('use_scanner');
        $this->vars['options']['default_paymethod'] = PayMethod::find(Settings::get('default_paymethod_id'));

        $this->asExtension('FormController')->update($recordId, $context);
    }

    /**
     * Update
     */
    public function preview($recordId = null, $context = null)
    {
        $Sale = Sale::find($recordId);

        $this->vars['sale'] = $Sale;
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

        $this->asExtension('FormController')->update($recordId, $context);
    }

    public function onPreview($recordId = null, $context = null)
    {

        return Redirect::to(Backend::url('awme/stockist/sales/preview/'.$recordId));
    }

    public function onChangeStatus($recordId = null, $context = null)
    {
        $status = Request::input('invoice_status');

        if(!$status)
            throw new ValidationException([
                   'error_message' => trans('Failed to change status')
                ]);

        $Sale = Sale::find($recordId);
        $Sale->setStatus($status);
        $Sale->save();

        return Redirect::to(Backend::url('awme/stockist/sales/preview/'.$recordId));
    }

    public function onCancelSale($recordId = null, $context = null)
    {
        $this->asExtension('ListController')->index();


        $model = $this->formCreateModelObject();
        $this->initForm($model);
        $this->vars['sale'] = Sale::find($recordId);
        return $this->makePartial('invoice_cancelation_form', []);
    }

    function isStatus($modelStatus, $arrayStatus){

        $status = in_array($modelStatus, $arrayStatus);

        return $status;
    }

    /**
     * Recalculate
     */
    public function onRecalculate($recordId = null, $context = null)
    {
        $Sale = Sale::find($recordId)->save();

        $SaleData = Sale::find($recordId);

        $this->vars['sale'] = $SaleData;
        $this->vars['options']['use_taxes'] = Settings::get('use_taxes');
        $this->asExtension('FormController')->update($recordId, $context);
    }

    public function onCheckin($recordId = null, $context = null)
    {

        $Sale = Sale::find($recordId);
        $Sale->checkIn();
        $Sale->save();
        
        return Redirect::to(Backend::url('awme/stockist/sales/preview/'.$recordId));
    }


    /**
     * onScannerCodeBar
     * Vista del formulario del scaner
     * @param  [type] $recordId [description]
     * @param  [type] $context  [description]
     * @return [type]           [description]
     */
    public function onScannerCodeBar($recordId = null, $context = null)
    {
        // Call the ListController behavior index() method
        $this->asExtension('ListController')->index();
        
        $Sale = Sale::find($recordId)->save();

        $SaleData = Sale::find($recordId);

        $this->vars['sale'] = $SaleData;

        $model = $this->formCreateModelObject();
        $this->initForm($model);
        return $this->makePartial('scanner_code_bar', []);
    }

    /**
     * onAddProductFormScanner
     * Funcion del formulario Scanner
     * Escanear producto y agregarlo a la venta.
     * por medio del codigo de barras.
     * @param  [type] $recordId [description]
     * @param  [type] $context  [description]
     * @return [type]           [description]
     */
    public function onAddProductFormScanner($recordId = null, $context = null)
    {
        //Product SKU (desde el input)
        $addProductId = Request::input('addProduct.sku');

        //Datos del Producto desde Product
        $Product = Product::where('sku', $addProductId)->first();

        /**
         * Si el producto no existe envia mensaje de error.
         * Caso contrario continua
         */
        if(!$Product):
            Flash::error(trans("awme.stockist::lang.sales.barcode_not_found"));
        else:
            //Producto existente en Venta.
            $ifExistInSaleProduct = SaleProduct::where('sale_id', $recordId)->where('product_id', $Product->id)->first();
            
            //Si existe en la lista de venta, updatea la cantidad con cada intro
            if($ifExistInSaleProduct){
                
                $SaleProduct = SaleProduct::find($ifExistInSaleProduct->id);
                $SaleProduct->quantity = ($SaleProduct->quantity + 1);
                $SaleProduct->save();

                Flash::success(e(trans('backend::lang.form.add')).' '.$Product->name);
            }

            //Si no existe en venta, lo agrega con cantidad y datos por default.
            else {
                $SaleProduct = new SaleProduct;
                $SaleProduct->sale_id       = $recordId;
                $SaleProduct->product_id    = $Product->id;
                $SaleProduct->quantity      = 1;
                $SaleProduct->save();

                Flash::success(e(trans('backend::lang.form.add')).' '.$Product->name);
            }
        endif;
    }
}