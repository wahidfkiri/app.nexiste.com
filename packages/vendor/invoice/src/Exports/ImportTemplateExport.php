<?php

namespace Vendor\Invoice\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Gabarit d'importation des factures : en-têtes attendus par InvoicesImport
 * (WithHeadingRow) + une ligne d'exemple pour guider l'utilisateur.
 */
class ImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'client', 'email_client', 'numero', 'reference', 'statut',
            'date_emission', 'echeance', 'conditions_paiement',
            'total_ht', 'total_ttc', 'reste_du', 'notes', 'description_ligne',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Société Exemple SARL', 'contact@exemple.fr', 'FAC-' . date('Y') . '-0001', 'CMD-001', 'sent',
                date('Y-m-d'), date('Y-m-d', strtotime('+30 days')), 30,
                '1000.00', '1200.00', '1200.00', 'Merci de votre confiance.', 'Prestation de conseil',
            ],
        ];
    }
}
