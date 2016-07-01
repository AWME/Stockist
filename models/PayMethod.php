<?php namespace AWME\Stockist\Models;

use Model;

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
}