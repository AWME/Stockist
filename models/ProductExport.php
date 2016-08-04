<?php namespace AWME\Stockist\Models;

class ProductExport extends \Backend\Models\ExportModel
{
    public function exportData($columns, $sessionKey = null)
    {
        $product = Product::all();
        $product->each(function($product) use ($columns) {
            $product->addVisible($columns);
        });
        return $product->toArray();
    }
}