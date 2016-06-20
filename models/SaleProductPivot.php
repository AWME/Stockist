<?php namespace AWME\Stockist\Models;

use Flash;

use October\Rain\Database\Pivot;
use AWME\Stockist\Classes\Calculator;

use AWME\Stockist\Models\Product;
use AWME\Stockist\Models\Sale;
use AWME\Stockist\Models\SaleProduct;
use AWME\Stockist\Models\SaleProductPivot;

/**
 * ClientPackagePivot Model
 */
class SaleProductPivot extends Pivot
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Rules
     */
    public $rules = [
        'quantity' => 'required|min:1',
    ];

    public function beforeSave()
    {

        /**
         * Set Product Subtotal
         */
        $this->setProductSubtotal();
    }

    public function afterSave()
    {
        /**
         * Set Subtotal de la venta.
         * Set Total de la venta.
         *
         * ejecuta funciones en beforeSave en Sale.
         */
        $Sale = Sale::find($this->sale_id)->save();
    }

    /**
     * setProductSubtotal()
     * Aplica el subtotal al producto
     * Operación ($price * $quantity)
     */
    public function setProductSubtotal()
    {
         /**
         * $price (pivot)
         * $price_sale (product)
         * @var integer #Precio de venta.
         */
        $price_sale = Product::find($this->product_id)->price_sale;

        if(!isset($this->attributes['price']) || empty($this->attributes['price']))
        {
            $this->price = $price_sale;
        } else {
            $this->price = ($this->attributes['price'] > 0) ? $this->attributes['price'] : $price_sale;
        }

        $this->subtotal = ($this->price * $this->quantity);
    }
}