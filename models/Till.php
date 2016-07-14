<?php namespace AWME\Stockist\Models;

use Model;
use BackendAuth;
use Backend\Models\User;
use ValidationException;

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
        'concept' => ['required'],
        'operation'     => ['required'],
        'description' => ['required','between:6,150'],
        'amount' => ['required','numeric','min:00.00', 'max:99999999.99'],
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
        if($this->operation == 'till_report')
        {
            if($this->concept == 'daily_report')
            {

                if(empty($this->record_data['report_date']))
                    throw new ValidationException([
                       'error_message' => trans(trans('awme.stockist::lang.errors.dates_are_required'))
                    ]);

            }elseif($this->concept == 'period_report')
            {
                if(empty($this->record_data['report_date_from']) || empty($this->record_data['report_date_to']))
                    throw new ValidationException([
                       'error_message' => trans(trans('awme.stockist::lang.errors.dates_are_required'))
                    ]);
            }
        }

        #Save Seller ID
        $this->seller_id = BackendAuth::getUser()->id;
    }



    /**
     * listFilterStatus
     * fitros de estado
     */
    public function listFilterCustomer($keyValue = null)
    {
        $users = User::all();

        $customers = [];

        foreach ($users as $key => $value) {
            $customers[$value->id] = $value->full_name;
        }

        return $customers;
    }

    public function scopeOperations($query, $order = 'desc')
    {
        $query->where('operation','!=', 'till_report');
        $query->orderBy('created_at', $order);

        return $query;
    }

    public function scopeAtDate($query, $from = null, $to = null)
    {
        $query->where('id','>', 1);
        $query->where('operation','!=', 'till_report');

        if($from)
            $query->where('created_at','>=', $this->formatDate($from).' 00:00:00');

        if($to)
            $query->where('created_at','<=', $this->formatDate($to).' 24:00:00');


        return $query;
    }

    public function scopeBySeller($query, $sellers = null)
    {
        if(is_array($sellers))
        {
            $query->whereIn('seller_id', $sellers);
        }

        return $query;
    }

    public function scopeGetSellers($query)
    {
        $query->groupBy('seller_id');

        return $query;
    }

    public function scopeGetTotal($query, $operation)
    {
        $query->where('operation', $operation);
        $query->select('amount');
        $queryResult = $query->get()->toArray();
        
        if(count($queryResult) >= 1)
            $profit = array_sum(array_column($queryResult, 'amount'));
        else $profit = '00.00';

        return $profit;
    }

    private function formatDate($date, $format = 'Y-m-d', $time = null)
    {
        #2016-07-10 18:49:24
        #to 2016-07-10 00:00:00
        return date($format, strtotime($date));
    }
}