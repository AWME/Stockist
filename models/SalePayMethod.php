<?php namespace AWME\Stockist\Models;

use Model;
use AWME\Stockist\Classes\Calculator as Calc;

/**
 * Model
 */
class SalePayMethod extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_stockist_sales_pay_methods';


    public function beforeSave()
    {

        #Total a cobrar
        $this->setTotalCharge();
    }

    /**
     * setTotalCharge
     * =============================
     * Aplicar el total a cobrar
     * Según los metodos de pago y sus taxes
     */
    public function setTotalCharge()
    {
        
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