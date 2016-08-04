<?php namespace AWME\Stockist\Models;

use Model;
use ValidationException;

/**
 * Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;

     /**
     * @var array Fillable fields
     */
    protected $fillable = ['id', 'name', 'slug', 'description', 'is_enabled', 'is_visible'];


    /**
     * @var array Validation rules
     */
    protected $rules = [
        'name' => ['required', 'between:1,255'],
        'slug' => [
            #'required',
            'alpha_dash',
            'between:1,255',
        ]
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = true;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_stockist_categories';

    public $hasMany = [
        'products' => ['AWME\Stockist\Models\Product', 'key'=>'category_id']
    ];


    public function beforeDelete()
    {
        if(count($this->products) > 0)
        {
            throw new ValidationException([
                   'error_message' => trans('awme.stockist::lang.messages.error_delete_used_category').count($this->products).' '.trans('awme.stockist::lang.products.products')
                ]);
        }
    }
}