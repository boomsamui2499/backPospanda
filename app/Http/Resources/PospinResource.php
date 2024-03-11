<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PospinResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $pricelist_id = $request->pricelist_id;
        $urlfix = \config('getURL.url');


        if ($urlfix == null || $urlfix == 0) {
            $url = url('/') . "/storage" . str_replace("public", "", $this->product->image);
            // var_dump($url);
        } else {
            $url = $urlfix . "/storage" . str_replace("public", "", $this->product->image);
            // var_dump($this->product);
        }
        return [
            'pos_pin_id' => $this->pos_pin_id,
            'sequence' => $this->sequence,
            'product' => $this->product,
            'image' => $this->product->image,
            'pricelist_by_id' => $this->productPricelistByPricelistId($pricelist_id)->get(),
            'image_url' => $url,

        ];
    }
}
