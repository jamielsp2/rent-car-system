<?php

namespace App\Http\Requests;

use App\Enums\FuelLevel;
use App\Enums\VehicleCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnInspectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['save', 'sign'])],
            'return_mileage' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'return_fuel_level' => ['nullable', Rule::enum(FuelLevel::class)],
            'return_exterior_condition' => ['nullable', Rule::enum(VehicleCondition::class)],
            'return_interior_condition' => ['nullable', Rule::enum(VehicleCondition::class)],
            'return_damage_marks' => ['nullable', 'json', 'max:30000'],
            'return_checklist' => ['nullable', 'array'],
            'return_checklist.*' => ['string', 'max:255'],
            'return_notes' => ['nullable', 'string', 'max:5000'],
            'return_extra_charges' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'return_charges_description' => ['nullable', 'string', 'max:2000'],
            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['string', 'max:500'],
            'return_images' => ['nullable', 'array', 'max:20'],
            'return_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'return_signer_name' => ['nullable', 'string', 'max:120'],
            'return_company_representative' => ['nullable', 'string', 'max:120'],
            'customer_signature' => ['nullable', 'string', 'max:1500000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'return_mileage' => 'kilometraje de llegada',
            'return_extra_charges' => 'cargos adicionales',
            'return_images.*' => 'fotos del vehículo',
        ];
    }
}
