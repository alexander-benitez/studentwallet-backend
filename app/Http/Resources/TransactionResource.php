<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'amount'      => (float) $this->amount,
            'type'        => $this->type,
            'description' => $this->description,
            'date'        => $this->date ? $this->date->format('Y-m-d') : null,
            'category'    => new CategoryResource($this->whenLoaded('category', $this->category)),
            'user_id'     => $this->user_id,
            'created_at'  => $this->created_at ? $this->created_at->toISOString() : null,
        ];
    }
}
