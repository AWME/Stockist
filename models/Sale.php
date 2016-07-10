<?php namespace AWME\Stockist\Models;

use Flash;
use Model;
use Request;
use BackendAuth;
use ValidationException;

use AWME\Stockist\Classes\Calculator as Calc;

use AWME\Stockist\Models\Till;
use AWME\Stockist\Models\Settings;
use AWME\Stockist\Models\SaleProduct;

/**
 * Model
 */
class Sale extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_stockist_sales';
    
    protected $jsonable = ['tax'];
    
    /*
     * Validation
     */
    protected $rules = [
        'fullname' => ['between:2,255'],
        'invoice' => [
            'between:1,25',
            'unique:awme_stockist_sales'
        ],
        'dni' => ['digits_between:6,16','numeric'],
        'phone' => ['digits_between:6,16','numeric'],
        'email' => ['email'],
    ];


    /**
     * @var array Relations
     */
    public $belongsTo = [
        'seller' => [
            'Backend\Models\User',
            'key' => 'seller_id',
        ],
    ];
    public $belongsToMany = [
        'products_pivot_model' => [
            'AWME\Stockist\Models\Product',
            'table' => 'awme_stockist_sales_products',
            'key'   => 'sale_id',
            'pivot' => ['quantity','price','subtotal'],
            'timestamps' => true,
            'pivotModel' => 'AWME\Stockist\Models\SaleProductPivot',
        ],
        'invoice_products' => [
            'AWME\Stockist\Models\Product',
            'table' => 'awme_stockist_sales_products',
            'key'   => 'sale_id',
            'pivot' => ['quantity','price','subtotal'],
            'timestamps' => true,
            'pivotModel' => 'AWME\Stockist\Models\SaleProductPivot',
        ],
        'pay_methods' => [
            'AWME\Stockist\Models\PayMethod',
            'table' => 'awme_stockist_sales_pay_methods',
            'key'   => 'sale_id',
            'pivot' => ['concept','total'],
            'timestamps' => true,
            'pivotModel' => 'AWME\Stockist\Models\SalePayMethodPivot',
        ],
        'invoice_pay_methods' => [
            'AWME\Stockist\Models\PayMethod',
            'table' => 'awme_stockist_sales_pay_methods',
            'key'   => 'sale_id',
            'pivot' => ['concept','total'],
            'timestamps' => true,
            'pivotModel' => 'AWME\Stockist\Models\SalePayMethodPivot',
        ],
        
    ];


/**
 * ===================================
 * EVENTS
 * ===================================
 *
 */

    public function beforeSave()
    {
        $this->setTaxes();      # Guarda los Sale.tax fields.
        $this->setSubtotal();   # Operación del subtotal de la venta.
        $this->setTotal();      # Operación del total de la venta.

        $this->seller_id = BackendAuth::getUser()->id;

    }

    public function afterDelete()
    {
        SaleProduct::where('sale_id', $this->id)->delete();
    }

    public function afterCreate()
    {
        $this->setInvoiceNumber();
    }
/**
 * ===================================
 * FUNCTIONS
 * ===================================
 *
 */
    /**
     * setTaxes()
     * Aplica el tax attr segun "taxes %/$"
     * para aplicar al subtotal
     */
    public function setTaxes()
    {   
        $taxes = Request::input('Sale.tax');
        $this->tax = $taxes;
    }


    /**
     * setSaleSubtotal()
     * Aplica el subtotal de la venta.
     * Operación (suma $sale_prices)
     */
    public function setSubtotal()
    {   
        $sales = SaleProduct::where('sale_id', $this->id)->get()->toArray();
        $sale_prices = array_column($sales, 'subtotal');

        $this->subtotal = Calc::suma($sale_prices);
    }

    /**
     * setTotal
     * ==========================
     * setea el Total de la venta
     * Sumatoria y operación final de la venta
     * 
     * @return $this->total 
     */
    public function setTotal()
    {
        $total = $this->subtotal;

        if($this->tax['type'] == "$"){
            
            $total = Calc::suma([$this->subtotal, $this->tax['amount']]); 

        }else if($this->tax['type'] == "%"){
            $total = Calc::suma([$this->subtotal, Calc::percent($this->tax['amount'], $this->subtotal)]); 
        }

        $this->total = $total;
    }


    /**
     * setStatus
     * ====================================
     * Ejecuciones de cambio de estado
     * 
     * @param string    $status
     * @return          status
     */
    public function setStatus($status = null)
    {
        if(!$status)
            $status = Request::input('invoice_status');

        #Requiere un estado
        if(!$status)
            throw new ValidationException([
                   'error_message' => trans('invoice_status does not exist')
                ]);

        /**
         * Proceso de ejecución según estado
         */
        switch ($status) {

            #PAUSAR VENTA
            case 'pause':
                    $this->validatePauseStatus();
                    Flash::success(trans('awme.stockist::lang.messages.status_pause_success'));
                break;


            #PRESUPUESTAR
            case 'budget':
                    $this->validateBudgetStatus();
                    Flash::success(trans('awme.stockist::lang.messages.status_budget_success'));
                break;


            #SEÑAR VENTA
            case 'senate':
                    $this->validateSenateStatus();
                    
                    //it's ok...
                    foreach ($this->pay_methods as $key => $value) {    
                        
                        $saleCode = trans('awme.stockist::lang.sales.shortsale');
                        $description  = $value['code'].' '.$saleCode.': #'.$this->id.' '.$this->invoice;

                        $this->putPayOnTill('deposit', 'sale_senate', $description, $value); 
                    }

                    Flash::success(trans('awme.stockist::lang.messages.status_senate_success'));
                break;


            #CANCELAR VENTA
            case 'canceled':
                    $this->validateCanceledStatus();

                    //it's ok...
                    $saleCode = trans('awme.stockist::lang.sales.shortsale');
                    $description = trans('awme.stockist::lang.invoice.cancelation').' '.$saleCode.': #'.$this->id.' '.$this->invoice;

                    #Reembolso en caja
                    if(Request::input('invoice_repayment'))
                        $this->putPayOnTill('withdraw', 'sale_cancelation', $description, [], $this->getTotalPaid('total')); 

                    #Reponer mercadería
                    if(Request::input('invoice_restock'))
                        $this->rePutStockOnProducts('add');

                    Flash::warning(trans('awme.stockist::lang.messages.status_canceled_success'));
                break;

            default:
                    # nothing
                    $this->status = $status;
                    Flash::warning('status now is '.$status);
                break;
        }
    }


    /**
     * validatePauseStatus
     * ============================
     * Validar pausa de venta
     * 
     * @return status
     */
    public function validatePauseStatus()
    {
        #Requiere sin metodos de pago
        if(count($this->pay_methods) >= 1)
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.errors.validate_status_pause_paymethods')
                ]);

        $this->status = 'pause';
    }


    /**
     * validateBudgetStatus
     * ============================
     * Validar presupuesto
     * 
     * @return status
     */
    public function validateBudgetStatus()
    {   
        #Requiere productos seleccionados
        if(!count($this->products_pivot_model) >= 1)
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.errors.validate_status_budget_products')
                ]);

        #Requiere sin metodos de pago agregados
        if(count($this->pay_methods) >= 1)
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.errors.validate_status_budget_paymethods')
                ]);

        $this->status = 'budget';
    }


    /**
     * validateSenateStatus
     * ============================
     * Validar venta señada
     * 
     * @return status
     */
    public function validateSenateStatus()
    {
        #Requiere metodos de pagos agregados
        if(!count($this->pay_methods) >= 1)
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.errors.validate_status_senate_paymethods')
                ]);

        $this->status = 'senate';
    }


    /**
     * validateCanceledStatus
     * ============================
     * Validar cancelación de venta
     * 
     * @return status
     */
    public function validateCanceledStatus()
    {  
        #Permitir cancelación segun estado actual
        if(in_array($this->status, ['open', 'pause', 'budget']))
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.errors.validate_status_canceled_status')
                ]);

        $this->status = 'canceled';
    }




    /**
     * putPayOnTill
     * ==========================
     * Crea movimientos de caja
     *     
     * @param  [string] $operation    | deposit, withdraw

     * @param  [string] $concept      | ej: new_sale, sale_cancelation
     * @param  [string] $description  | ej: TCD VTA #1 INV 11123123
     * @param  [array]  $payData      | json data
     * @param  [int]    $total        | monto 0.00
     * 
     */
    public function putPayOnTill($operation, $concept, $description, $payData, $total = null)
    {

        $Till = new Till;
        $Till->operation    = $operation;
        $Till->concept      = $concept;
        $Till->description  = $description;

        $Till->record_data  = $payData;
        $Till->amount       = ($total) ? $total : $payData['pivot']['total'];
        $Till->save();
        
    }


    /**
     * rePutStockOnProducts
     * ========================
     * Reponer/Quita stock
     * recuento de mercadería según productos vendidos/repuestos
     * desde una venta
     * add, deduct
     */
    public function rePutStockOnProducts($operation = null)
    {
        $invoiceProducts = $this->products_pivot_model;

        if(count($invoiceProducts) >= 1)
        {
            foreach ($invoiceProducts as $key => $value) {
                $Product = Product::find($value['pivot']['product_id']);

                if($operation == 'add')
                    $result = $Product->stock + $value['pivot']['quantity'];
                else if($operation == 'deduct')
                    $result = $Product->stock - $value['pivot']['quantity'];
                else $result = $Product->stock;

                $Product->stock = ($result);
                $Product->save();
            }
        }
    }


    /**
     * checkIn
     * =======================
     * Mete los pagos en caja
     * Cierra la venta
     */
    public function checkIn()
    {

        #Requiere productos
        if(!count($this->products_pivot_model) >= 1)
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.errors.validate_status_close_products')
                ]);

        #Requiere pagos
        if(!count($this->pay_methods) >= 1)
        {
            #PAGO AUTOMATICO
            if(Settings::get('use_auto_payment') && !empty(Settings::get('default_paymethod_id')))
            {
                $Payment = new SalePayMethod;
                $Payment->sale_id       = $this->id;
                $Payment->pay_method_id = Settings::get('default_paymethod_id');
                $Payment->concept       = $this->total;
                $Payment->save();
            }else {
                throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.errors.validate_status_close_paymethods')
                   ]);
            }   
        }

        #Requiere pago exacto
        if($this->getTotalPaid('concept') != $this->total)
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.sales.error_total_paid')
                ]);


        //it's ok...
        # confirma la venta y envia a caja.
        $this->makeSale();

        Flash::success(trans('awme.stockist::lang.messages.closed_sale_success'));
        
        $this->status = 'closed';
    }

    public function makeSale()
    {
        $Paymethods = Sale::find($this->id)->pay_methods;

        foreach ($Paymethods as $key => $value) 
        {    
            $saleCode = trans('awme.stockist::lang.sales.shortsale');
            #Paymethod code, VTA, #number, Invoice Number
            $description  = $value['code'].' '.$saleCode.': #'.$this->id.' '.$this->invoice;

            #deposito, concepto, descripcion, valor.
            $this->putPayOnTill('deposit', 'sale_payment', $description, $value); 
        }

        #Quita mercadería vendida del stock
        $this->rePutStockOnProducts('deduct');
    }



    /**
     * getTotalPaid
     * Obtener el pago total abonado
     * de los metodos y montos aplicados a la venta
     * 
     * pluck options:
     * concept: monto de facturación
     * total: monto bruto abonado 
     * 
     * @return int total
     */
    public function getTotalPaid($pluck = 'concept')
    {   
        $totals = SalePayMethod::where('sale_id', $this->id)->get()->pluck($pluck)->toArray();        
        $total = array_sum($totals);
        return number_format($total, 2, '.', '');
    }


/**
 * ===================================
 * MUTATORS
 * ===================================
 *
 */

    /**
     * Setear nombre por defecto a las ventas anonimas
     * @return string fullname
     */
    public function setFullNameAttribute($value)
    {
        $default_name = Settings::get('default_clientname');
        if(!$value)
            if($default_name)
                $value = $default_name;
            else $value = trans('awme.stockist::lang.settings.default_client_name_df');
       
       $this->attributes['fullname'] = ucfirst($value);
    }
    

    public function setInvoiceNumber()
    {
        if(empty(Request::input('Sale.invoice')))
            $this->invoice = str_pad((int) $this->id, 6,"0",STR_PAD_LEFT).'-'.str_pad((int) random_int(1, 99999999), 8,"0",STR_PAD_LEFT);
            $this->save();
    }

/**
 * ===================================
 * SCOPES
 * ===================================
 *
 */
    /**
     * listFilterStatus
     * fitros de estado
     */
    public function listFilterStatus($keyValue = null)
    {
        return [
                'open' => trans('awme.stockist::lang.invoice.tag_open'),
                'senate' => trans('awme.stockist::lang.invoice.tag_senate'),
                'pause' => trans('awme.stockist::lang.invoice.tag_pause'),
                'budget' => trans('awme.stockist::lang.invoice.tag_budget'),
                'closed' => trans('awme.stockist::lang.invoice.tag_closed'),
                'canceled' => trans('awme.stockist::lang.invoice.tag_canceled'),
            ];
    }

    /*public function scopeShowToday($query)
    {
        $show = date("Y-m-d");
        $date = $show.' 00:00:00';
        return $query->where('created_at','>=', $date);
    }*/

}