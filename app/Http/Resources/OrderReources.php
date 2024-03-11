<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderReources extends JsonResource
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
            'order_id' => $this->order_id,
            'pricelist_id' => $this->pricelist_id,
            'order_number' => $this->order_number,
            'session_receipt_number' => $this->session_receipt_number,
            'year_receipt_number' => $this->year_receipt_number,
            'subtotal' => $this->subtotal,
            'vat' => $this->vat,
            'total' => $this->total,
            'total_payment' => $this->total_payment,
            'total_recive' => $this->total_recive,
            'total_margin' => $this->total_margin,
            'price_change' => $this->price_change,
            'created_datetime' => $this->created_datetime,
            'is_vat' => $this->is_vat,
            'type' => $this->type,
            'payment_amount' => $this->payment_amount,
            'active' => $this->active,
            'user' => $this->user,
            'member' => $this->member,
            'possesion' => $this->possesion,
            'orderline' => $this->orderline,
            'orderpayment' => $this->orderpayment,
        ];
    }
}
