<?php namespace AWME\Stockist\Models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'awme_stockist_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'default_paymethod'    => ['AWME\Stockist\Models\PayMethod'],
    ];
}