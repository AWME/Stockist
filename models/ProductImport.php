<?php namespace AWME\Stockist\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductImport extends \Backend\Models\ImportModel
{
    /**
     * @var array The rules to be applied to the data.
     */
    public $rules =  [
            'id' => 'required', 
            'name' => 'required', 
            'sku' => 'required'
        ];

    public function importData($results, $sessionKey = null)
    {
        foreach ($results as $row => $data) {

            try {
                $entry = Product::findOrFail($data['id']);
                $entry->fill($data);
                $entry->save();
                $this->logUpdated();
            }
            catch (ModelNotFoundException $ex) {
                $entry = new Product;
                $entry->fill($data);
                $entry->save();
                $this->logCreated();
            }
            catch (\Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }

        }
    }
}
?>