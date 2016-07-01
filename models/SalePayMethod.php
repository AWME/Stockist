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
}