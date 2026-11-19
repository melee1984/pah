<?php

namespace App\Services;

use App\Products;

class MenuSelection
{
    public function select(Products $product, array $selections): array
    {
        $content = [];
        $total = 0.0;
        $commissionTotal = 0.0;
        $selectedHeaders = [];
        $selectedDetails = [];

        foreach ($selections as $selection) {
            $header = $product->variants->firstWhere('id', (int) $selection['variant_id']);
            $detail = $header?->product_details->firstWhere(
                'id',
                (int) $selection['product_detail_id']
            );

            if (! $header || ! $detail) {
                return ['error' => 'One or more selected variants are unavailable for this item.'];
            }

            if (isset($selectedDetails[$detail->id])) {
                return ['error' => 'The same variant option cannot be selected more than once.'];
            }

            if (! $header->is_multiple && isset($selectedHeaders[$header->id])) {
                return ['error' => "Only one option may be selected for {$header->title}."];
            }

            $selectedHeaders[$header->id] = true;
            $selectedDetails[$detail->id] = true;
            $price = (float) str_replace(',', '', $detail->getPrice());
            $commission = (float) str_replace(',', '', $detail->getPriceComm());
            $content[] = [
                'variant_id' => $header->id,
                'variant_detail_id' => $detail->id,
                'title' => trim($detail->title.' '.$header->title),
                'price' => number_format($price, 2, '.', ''),
            ];
            $total += $price;
            $commissionTotal += $commission;
        }

        $missingRequired = $product->variants
            ->where('is_required', true)
            ->reject(fn ($header) => isset($selectedHeaders[$header->id]))
            ->first();

        if ($missingRequired) {
            return ['error' => "Please select an option for {$missingRequired->title}."];
        }

        usort($content, fn ($left, $right) => [
            $left['variant_id'],
            $left['variant_detail_id'],
        ] <=> [$right['variant_id'], $right['variant_detail_id']]);

        return [
            'content' => $content,
            'total' => $total,
            'commission_total' => $commissionTotal,
        ];
    }
}
