<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\SiteContent;

/**
 * Contract configuration, stored in `settings`. The company data printed on
 * the contract (name, address, phone, e-mail, logo) comes from Configuración →
 * Web pública; the RNC and the representative from Configuración → Contrato.
 */
class ContractSettings
{
    public const PREFIX = 'rentcar_';

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get(self::PREFIX . $key, $default);
    }

    public function title(): string
    {
        return $this->get('contract_title', 'Contrato de Arrendamiento de Vehículo');
    }

    public function minPhotos(): int
    {
        $value = $this->get('contract_min_photos');

        return $value === null ? 4 : max(0, (int) $value);
    }

    public function companyRepresentative(): ?string
    {
        return $this->get('contract_company_representative');
    }

    public function footerNote(): ?string
    {
        return $this->get('contract_footer_note');
    }

    public function checklistItems(): array
    {
        $items = $this->get('contract_checklist_items') ?: implode("\n", self::DEFAULT_CHECKLIST);

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $items))));
    }

    public function terms(): string
    {
        return $this->get('contract_terms') ?: ContractTerms::DEFAULT;
    }

    public function company(): array
    {
        $contact = SiteContent::contact();

        return [
            'name' => SiteContent::name(),
            'rnc' => $this->get('contract_company_rnc'),
            'address' => $contact['address'] ?: null,
            'phone' => $contact['phone'] ?: null,
            'email' => $contact['email'] ?: null,
            'logo' => SiteContent::logo(),
        ];
    }

    public const DEFAULT_CHECKLIST = [
        'Llanta de repuesto',
        'Gato',
        'Llave de rueda',
        'Triángulo de emergencia',
        'Extintor',
        'Matrícula / documentos del vehículo',
        'Seguro vigente',
        'Radio / sistema de audio',
        'Alfombras',
        'Espejos completos',
        'Tapas de rueda',
        'Llave / control remoto',
    ];
}
