<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductListingResource extends JsonResource
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
            'id' => $this->id,
            'itemType' => $this->is_service == true ? 'service' : 'product',
            'name' => $this->name,
            'label' => $this->name . ' [' . $this->code . ']',
            'slug' => $this->slug,
            'subCategory' => $this->proSubCategory ? new ProductSubCategoryResource($this->whenLoaded('proSubCategory')) : null,
            'category' => $this->proSubCategory && $this->proSubCategory->category ? new ProductCategoryResource($this->proSubCategory->category) : null,
            'code' => $this->code,
            'itemModel' => $this->model,
            'itemUnit' => new UnitResource($this->productUnit),
            'inventoryCount' => $this->inventory_count,
            'alertQty' => $this->alert_qty,
            'regularPrice' => $this->regular_price,
            'sellingPrice' => $this->sellingPrice(),
            'discount' => $this->discount,
            'discountAmount' => $this->discountAmount(),
            'taxAmount' => $this->taxAmount(),
            'availableQty' => $this->inventory_count > 0 ? $this->inventory_count : 0,
            'alertQty' => $this->alert_qty,
            'note' => $this->note,
            'status' => (int) $this->status,
            'image' => $this->image_path ? asset('/images/products/' . $this->image_path) : '',
        ];
    }
}
