<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order_number' => $this->purchase_order_number,
            'user_id' => $this->user_id,
            'create_datetime' => $this->create_datetime,
            'comment' => $this->comment,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'total' => $this->total,
            'status' => $this->status,
            'user' => $this->user,
            'supplier' => $this->supplier,
            'purchase_order_line' => $this->purchase_order_line,
        ];
    }
}
