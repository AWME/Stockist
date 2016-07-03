<?php namespace AWME\Stockist\Models;

use Flash;
use Model;
use Request;

use AWME\Stockist\Classes\Calculator as Calc;

use AWME\Stockist\Models\Sale;
use AWME\Stockist\Models\Settings;
use AWME\Stockist\Models\SaleProduct;

/**
 * Model
 */
class Sale extends Model
{
    use \October\Rain\Database\Traits\Validation;

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
     * @var string The database table used by the model.
     */
    public $table = 'awme_stockist_sales';

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'products_pivot_model' => [
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
        
    ];

    public function beforeSave()
    {
        $this->setTaxes();      # Guarda los Sale.tax fields.
        $this->setSubtotal();   # Operación del subtotal de la venta.
        $this->setTotal();      # Operación del total de la venta.
        #$this->setPaidOut();    # Pago y Cambio
    }

    public function afterDelete()
    {
        SaleProduct::where('sale_id', $this->id)->delete();
    }

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

    public function setPaidOut()
    {
        $taxes = $this->tax;
        $taxes['change'] = ($this->tax['paid_out'] - $this->total);

        $this->tax = $taxes;
    }
}