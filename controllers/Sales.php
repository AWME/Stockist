<?php namespace AWME\Stockist\Controllers;

use Flash;
use Request;
use BackendMenu;
use Backend\Classes\Controller;

use AWME\Stockist\Models\Sale;
use AWME\Stockist\Models\Product;
use AWME\Stockist\Models\SaleProduct;
use AWME\Stockist\Models\SaleProductPivot;

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
    }

    /**
     * Update
     */
    public function update($recordId = null, $context = null)
    {
        $Sale = Sale::find($recordId);

        $this->vars['sale'] = $Sale;

        $this->asExtension('FormController')->update($recordId, $context);
    }

    /**
     * Recalculate
     */
    public function onRecalculate($recordId = null, $context = null)
    {
        $Sale = Sale::find($recordId)->save();

        $SaleData = Sale::find($recordId);

        $this->vars['sale'] = $SaleData;

        $this->asExtension('FormController')->update($recordId, $context);

        #Flash::info(e(trans('awme.stockist::lang.sales.invoice_recalculate')));
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