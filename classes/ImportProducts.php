<?php namespace AWME\Stockist\Classes;

use AWME\Stockist\Models\Product;
use AWME\Stockist\Models\Category;
use Db;
use Flash;
use Redirect;

use Storage;

class ImportProducts{


    public function getProducts()
    {
        return json_decode(Storage::get('/media/listing.json'));
    }

    public function import(){

        foreach ($this->getProducts() as $keyCat => $cat) {
            
            $Category = new Category;
            $Category->name = $cat->category;
            $Category->save();

            if(isset($cat->products)){
                foreach ($cat->products as $keyProd => $prod) {

                    $exists = Product::where('sku',$prod->code)->count();
                    $Product = new Product;
                    $Product->category_id = $keyCat;
                    $Product->sku = $prod->code;

                    if(!$exists)
                    $Product->forceSave();
                }
            }
        }

        return Product::get();
    }

    public function remote($id)
    {
        return json_decode(file_get_contents("http://awscrap.awebsome.me/product/".$id));
    }

    public function setRemoteData()
    {
        $Products = Db::table('awme_stockist_products')->whereNull("name")->where("is_enabled",0)->take(100)->get();

        foreach ($Products as $key => $value) {
            
            $Product = Product::find($value->id);

            $data = $this->remote($value->sku);

            $Product->name = $data->name;
            $Product->image = $data->img;
            $Product->is_enabled = 1;
            $Product->is_stockable = 1;

            $Product->forceSave();
        }
        
        return $Products;
    }
}
?>