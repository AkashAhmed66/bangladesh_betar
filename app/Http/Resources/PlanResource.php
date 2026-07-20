<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'description' => $this->description,
            'price_monthly' => (float) $this->price_monthly,
            'price_annual' => (float) $this->price_annual,
            'currency' => $this->currency,
            'trial_days' => $this->trial_days,
            'features' => $this->features,
        ];
    }
}
