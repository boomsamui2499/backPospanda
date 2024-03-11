<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

    public function __construct($resource, $pricelist_id)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->resource = $resource;
        $this->pricelist_id = $pricelist_id;
    }


    public function toArray($request)
    {
        $urlfix = \config('getURL.url');

        if (!$urlfix == null) {
            $url = $urlfix . "/storage" . str_replace("public", "", $this->image);
        } else {
            $url = url('/') . "/storage" . str_replace("public", "", $this->image);
        }
        return [
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'type' => $this->type,
            'image' => $this->image,
            // 'image_url' => url('/') . "/storage" . str_replace("public", "", $this->image),
            'image_url' => $url,
            'barcode' => $this->barcode,
            'is_vat' => $this->is_vat,
            'is_scale' => $this->is_scale,
            'stock_qty' => $this->stock_qty,
            'current_average_cost' => $this->current_average_cost,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'category' => $this->category,
            'product_uom' => $this->product_uom,
            'pricelist' => $this->productPricelist,
        ];
    }
}
