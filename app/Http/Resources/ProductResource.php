<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'itemType' => $this->is_service == true ? 'service' : 'product',
            'name' => $this->name,
            'label' => $this->name.' ['.$this->code.']',
            'slug' => $this->slug,
            'code' => is_numeric($this->code) ? str_pad($this->code, 5, '0', STR_PAD_LEFT) : $this->code,
            'itemModel' => $this->model,
            'symbology' => $this->barcode_symbology,
            'subCategory' => $this->proSubCategory ? new ProductSubCategoryResource($this->whenLoaded('proSubCategory')) : null,
            'category' => $this->proSubCategory && $this->proSubCategory->category ? new ProductCategoryResource($this->proSubCategory->category) : null,
            'itemUnit' => new UnitResource($this->productUnit),
            'itemBrand' => new BrandResource($this->productBrand),
            'itemTax' => new VatRateResource($this->productTax),
            'taxType' => $this->tax_type,
            'taxAmount' => $this->taxAmount(),
            'avgPurchasePrice' => $this->purchase_price,
            'servicePurchasePrice' => $this->purchase_price,
            'regularPrice' => $this->regular_price,
            'sellingPrice' => $this->sellingPrice(),
            'openingStockCount' => $this->opening_stock_count,
            'openingStockUnitPrice' => $this->opening_stock_unit_price,
            'discount' => $this->discount,
            'discountAmount' => $this->discountAmount(),
            'availableQty' => $this->inventory_count > 0 ? $this->inventory_count : 0,
            'alertQty' => $this->alert_qty,
            'note' => $this->note,
            'status' => (int) $this->status,
            'image' => $this->image_path ? asset('/images/products/'.$this->image_path) : '',
        ];
    }
}
