<?php namespace AWME\Stockist\Models;

use Flash;

use October\Rain\Database\Pivot;

use AWME\Stockist\Models\PayMethod;

use AWME\Stockist\Classes\Calculator as Calc;

/**
 * ClientPackagePivot Model
 */
class SalePayMethodPivot extends Pivot
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Rules
     */
    public $rules = [
        'concept' => 'required|numeric|min:0',
    ];

    public function beforeSave()
    {

        /**
         * Set Product Subtotal
         */
        $this->setTotalCharge();
    }

    /**
     * setProductSubtotal()
     * Aplica el subtotal al producto
     * Operación ($price * $quantity)
     */
    public function setTotalCharge()
    {
         /**
         * $price (pivot)
         * $price_sale (product)
         * @var integer #Precio de venta.
         */
        $PayMethod = PayMethod::find($this->pay_method_id);

       	$payConcept = $this->concept;

        if($PayMethod->tax_type == "$"){
            
            $total = Calc::suma([$payConcept, $PayMethod->tax_amount]); 

        }else if($PayMethod->tax_type == "%"){
            $total = Calc::suma([$payConcept, Calc::percent($PayMethod->tax_amount, $payConcept)]); 
        }else $total = $payConcept;

        $this->total = $total;
    }
}