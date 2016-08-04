<?php namespace AWME\Stockist\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryImport extends \Backend\Models\ImportModel
{
    /**
     * @var array The rules to be applied to the data.
     */
    public $rules =  [
            'id' => 'required', 
            'name' => 'required',
        ];

    public function importData($results, $sessionKey = null)
    {
        foreach ($results as $row => $data) {

            try {
                $entry = Category::findOrFail($data['id']);
                $entry->fill($data);
                $entry->save();
                $this->logUpdated();
            }
            catch (ModelNotFoundException $ex) {
                $entry = new Category;
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