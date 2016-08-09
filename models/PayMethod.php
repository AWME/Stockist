<?php namespace AWME\Stockist\Models;

use Model;
use ValidationException;
use AWME\Stockist\Models\SalePayMethod;
/**
 * Model
 */
class PayMethod extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Validation rules
     */
    protected $rules = [
        'name' => ['required', 'between:1,300'],
        'tax_amount' => ['numeric'],
        'code' => [
            'required',
            'alpha_dash',
            'between:1,8',
            'unique:awme_stockist_pay_methods'
        ],
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_stockist_pay_methods';

    public function beforeDelete()
    {
        $sales = SalePayMethod::where('pay_method_id', $this->id)->count();
        if($sales > 0)
        {
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.messages.error_delete_used_paymethod').$sales.' '.trans('awme.stockist::lang.sales.sales')
                ]);
        }
    }
}