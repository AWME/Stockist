<?php namespace AWME\Stockist\Models;

use Model;
use ValidationException;
use AWME\Stockist\Models\SaleProduct;
/**
 * Model
 */
class Product extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'categories'    => ['AWME\Stockist\Models\Category', 'key' => 'category_id'],
        'category'    => ['AWME\Stockist\Models\Category', 'key' => 'category_id'],
    ];

    
    /**
     * @var array Fillable fields
     */
    protected $fillable = ['id','category_id','name','sku','slug','stock','price_cost','price_sale','iva','image'];

    /**
     * @var array Validation rules
     */
    protected $rules = [
        'name' => ['required', 'between:1,255'],
        'sku' => ['required', 'unique:awme_stockist_products'],
        'slug' => [
            'unique:awme_stockist_products',
            'alpha_dash',
            'between:1,255',
        ],
        'stock' => ['required','numeric'],
        'price_cost' => ['required','numeric','min:00.00', 'max:99999999.99'],
        'price_sale' => ['required','numeric','min:00.00', 'max:99999999.99'],
        'iva' => ['numeric', 'max:99.99']
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_stockist_products';

    public function beforeDelete()
    {
        $sales = SaleProduct::where('product_id', $this->id)->count();
        if($sales > 0)
        {
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.messages.error_delete_sold_item').$sales.' '.trans('awme.stockist::lang.sales.sales')
                ]);
        }
    }
}