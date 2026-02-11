<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\PatientProfileChangeRequestRepository;
use App\Repositories\PatientRepository;

final class PatientProfileChangeRequestService
{
    public function __construct(private readonly Container $container) {}

    public function createForCurrentPatient(array $requestedFields, string $ip): void
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();

        if ($clinicId === null || $patientId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente não encontrado.');
        }

        $fields = [
            'name' => trim((string)($requestedFields['name'] ?? '')),
            'email' => trim((string)($requestedFields['email'] ?? '')),
            'phone' => trim((string)($requestedFields['phone'] ?? '')),
            'birth_date' => trim((string)($requestedFields['birth_date'] ?? '')),
            'address_street' => trim((string)($requestedFields['address_street'] ?? '')),
            'address_number' => trim((string)($requestedFields['address_number'] ?? '')),
            'address_complement' => trim((string)($requestedFields['address_complement'] ?? '')),
            'address_district' => trim((string)($requestedFields['address_district'] ?? '')),
            'address_city' => trim((string)($requestedFields['address_city'] ?? '')),
            'address_state' => trim((string)($requestedFields['address_state'] ?? '')),
            'address_zip' => trim((string)($requestedFields['address_zip'] ?? '')),
        ];

        if ($fields['name'] === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }

        // Store only the fields that are actually changing (optional, but keeps payload clean)
        $payload = [];
        foreach (['name', 'email', 'phone', 'birth_date'] as $k) {
            $v = (string)$fields[$k];
            $current = trim((string)($patient[$k] ?? ''));
            if ($v !== '' && $v !== $current) {
                $payload[$k] = $v;
            }
        }

        $hasAnyAddressPiece = false;
        foreach (['address_street', 'address_number', 'address_complement', 'address_district', 'address_city', 'address_state', 'address_zip'] as $k) {
            if ((string)$fields[$k] !== '') {
                $hasAnyAddressPiece = true;
                break;
            }
        }

        if ($hasAnyAddressPiece) {
            $street = (string)$fields['address_street'];
            $number = (string)$fields['address_number'];
            $complement = (string)$fields['address_complement'];
            $district = (string)$fields['address_district'];
            $city = (string)$fields['address_city'];
            $state = (string)$fields['address_state'];
            $zip = (string)$fields['address_zip'];

            $line1 = trim($street
                . ($number !== '' ? (', ' . $number) : '')
                . ($complement !== '' ? (' - ' . $complement) : '')
            );
            $line2 = trim(
                ($district !== '' ? ($district . ' - ') : '')
                . $city
                . ($state !== '' ? ('/' . $state) : '')
            );
            $line3 = $zip !== '' ? ('CEP: ' . $zip) : '';
            $address = implode("\n", array_values(array_filter([$line1, $line2, $line3], static fn($v) => is_string($v) && trim($v) !== '')));

            $currentAddress = trim((string)($patient['address'] ?? ''));
            if (trim($address) !== '' && $address !== $currentAddress) {
                $payload['address'] = $address;
                $payload['address_parts'] = [
                    'street' => $street,
                    'number' => $number,
                    'complement' => $complement,
                    'district' => $district,
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                ];
            }
        }

        if ($payload === []) {
            throw new \RuntimeException('Nenhuma alteração foi detectada.');
        }

        $repo = new PatientProfileChangeRequestRepository($pdo);
        $repo->create($clinicId, $patientId, $patientUserId, $payload);
    }
}
