<?php namespace AWME\Stockist\Models;

use Model;
use BackendAuth;

/**
 * Model
 */
class Till extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = true;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'awme_stockist_tills';

    public $jsonable = ['record_data'];
    
    /**
     * @var array Relations
     */
    public $belongsTo = [
        'seller' => [
            'Backend\Models\User',
            'key' => 'seller_id',
        ],
    ];

    public function beforeSave()
    {
       $this->seller_id = BackendAuth::getUser()->id;
    }
}