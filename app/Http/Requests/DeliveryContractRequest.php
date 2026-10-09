<?php

namespace App\Http\Requests;

use App\Enums\FuelLevel;
use App\Enums\VehicleCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['save', 'sign'])],
            'pickup_mileage' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'pickup_fuel_level' => ['nullable', Rule::enum(FuelLevel::class)],
            'exterior_condition' => ['nullable', Rule::enum(VehicleCondition::class)],
            'interior_condition' => ['nullable', Rule::enum(VehicleCondition::class)],
            'existing_damages' => ['nullable', 'string', 'max:5000'],
            'damage_marks' => ['nullable', 'json', 'max:30000'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['string', 'max:500'],
            'pickup_images' => ['nullable', 'array', 'max:20'],
            'pickup_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'signer_name' => ['nullable', 'string', 'max:120'],
            'signer_document' => [Rule::requiredIf($this->input('action') === 'sign'), 'nullable', 'string', 'max:60'],
            'company_representative' => ['nullable', 'string', 'max:120'],
            'customer_signature' => ['nullable', 'string', 'max:1500000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pickup_mileage' => 'kilometraje de salida',
            'pickup_images.*' => 'fotos del vehículo',
            'signer_document' => 'cédula / pasaporte',
        ];
    }
}
