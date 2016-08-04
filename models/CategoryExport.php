<?php namespace AWME\Stockist\Models;

class CategoryExport extends \Backend\Models\ExportModel
{
    public function exportData($columns, $sessionKey = null)
    {
        $entry = Category::all();
        $entry->each(function($entry) use ($columns) {
            $entry->addVisible($columns);
        });
        return $entry->toArray();
    }
}