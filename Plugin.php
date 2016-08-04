<?php namespace AWME\Stockist;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{   
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'awme.stockist::lang.settings.settings_label',
                'description' => 'awme.stockist::lang.settings.settings_description',
                'category'    => 'Stockist',
                'icon'        => 'icon-cubes',
                'class'       => 'AWME\Stockist\Models\Settings',
                'order'       => 10,
                'keywords'    => 'awme.stockist::lang.settings.keywords',
                'permissions' => ['awme.stockist.settings']
            ]
        ];
    }
}
