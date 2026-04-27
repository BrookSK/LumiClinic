<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

final class WhatsappTemplateRenderer
{
    /**
     * Aliases em português → chave interna em inglês.
     * Permite que a cliente use {nome_paciente} ou {patient_name} — ambos funcionam.
     */
    private const PT_ALIASES = [
        'nome_paciente'     => 'patient_name',
        'nome_clinica'      => 'clinic_name',
        'data'              => 'date',
        'horario'           => 'time',
        'nome_profissional' => 'professional_name',
        'nome_servico'      => 'service_name',
        'link_confirmacao'  => 'confirm_url',
        'link_anamnese'     => 'anamnesis_url',
        'link'              => 'click_url',
    ];

    /** @param array<string,string> $vars */
    public function render(string $templateBody, array $vars): string
    {
        $out = $templateBody;

        foreach ($vars as $k => $v) {
            $key = trim((string)$k);
            if ($key === '') {
                continue;
            }
            $out = str_replace('{' . $key . '}', (string)$v, $out);
        }

        // Substituir aliases em português pelas mesmas variáveis
        foreach (self::PT_ALIASES as $pt => $en) {
            if (isset($vars[$en])) {
                $out = str_replace('{' . $pt . '}', (string)$vars[$en], $out);
            }
        }

        return $out;
    }
}
