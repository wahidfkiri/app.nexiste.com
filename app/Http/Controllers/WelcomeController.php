<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Vendor\Extensions\Models\Extension;

class WelcomeController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $extensions = $this->resolveExtensions();
        $pillars = $this->buildPillars();

        return view('welcome', [
            'appName' => config('app.name', 'Nexus CRM'),
            'heroApps' => $this->buildHeroApps($extensions),
            'pillars' => $pillars,
            'workflowSteps' => $this->buildWorkflowSteps(),
            'extensionCategories' => $this->buildExtensionCategories($extensions),
            'highlights' => $this->buildHighlights(),
            'pricingPeriods' => $this->buildPricingPeriods(),
            'stats' => [
                ['value' => $pillars->count(), 'suffix' => '', 'label' => 'piliers metier'],
                ['value' => $extensions->count(), 'suffix' => '', 'label' => 'integrations actives'],
                ['value' => 4, 'suffix' => '', 'label' => 'formats d export et partage'],
                ['value' => 24, 'suffix' => '/7', 'label' => 'pilotage cloud et desktop'],
            ],
        ]);
    }

    private function resolveExtensions(): Collection
    {
        if (!Schema::hasTable('extensions')) {
            return collect();
        }

        return Extension::query()
            ->where('status', 'active')
            ->orderByDesc('active_installs_count')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Extension $extension) {
                return [
                    'name' => (string) $extension->name,
                    'slug' => (string) $extension->slug,
                    'tagline' => trim((string) ($extension->tagline ?: $extension->description ?: '')),
                    'icon_url' => $extension->icon_url,
                    'icon_class' => (string) ($extension->icon_class ?: 'fas fa-puzzle-piece'),
                    'accent' => (string) ($extension->icon_bg_color ?: $extension->category_color ?: '#2563eb'),
                    'category' => (string) $extension->category,
                    'category_label' => (string) $extension->category_label,
                    'category_icon' => (string) $extension->category_icon,
                    'category_color' => (string) $extension->category_color,
                ];
            })
            ->values();
    }

    private function buildHeroApps(Collection $extensions): array
    {
        $scatter = [
            ['x' => 11, 'y' => 16, 'size' => 92, 'drift' => 9],
            ['x' => 20, 'y' => 31, 'size' => 76, 'drift' => 11],
            ['x' => 13, 'y' => 54, 'size' => 84, 'drift' => 10],
            ['x' => 21, 'y' => 73, 'size' => 70, 'drift' => 12],
            ['x' => 29, 'y' => 84, 'size' => 78, 'drift' => 9],
            ['x' => 81, 'y' => 14, 'size' => 86, 'drift' => 10],
            ['x' => 89, 'y' => 34, 'size' => 74, 'drift' => 12],
            ['x' => 82, 'y' => 56, 'size' => 90, 'drift' => 11],
            ['x' => 90, 'y' => 75, 'size' => 72, 'drift' => 10],
            ['x' => 72, 'y' => 85, 'size' => 78, 'drift' => 9],
            ['x' => 8, 'y' => 36, 'size' => 66, 'drift' => 10],
            ['x' => 94, 'y' => 48, 'size' => 68, 'drift' => 11],
            ['x' => 27, 'y' => 13, 'size' => 64, 'drift' => 13],
            ['x' => 74, 'y' => 18, 'size' => 62, 'drift' => 12],
        ];

        return $extensions
            ->take(count($scatter))
            ->values()
            ->map(function (array $extension, int $index) use ($scatter) {
                $position = $scatter[$index];

                return $extension + [
                    'x' => $position['x'],
                    'y' => $position['y'],
                    'size' => $position['size'],
                    'delay' => round($index * 0.14, 2),
                    'drift' => $position['drift'],
                ];
            })
            ->all();
    }

    private function buildPillars(): Collection
    {
        return collect([
            [
                'eyebrow' => 'CRM centralise',
                'title' => 'Clients, opportunites et historique unifies dans un seul cockpit.',
                'body' => 'Regroupez contacts, contexte commercial, relances, echanges et prochaines actions dans une vue lisible par toute l equipe.',
                'points' => ['fiches clients structurees', 'rappels et suivis', 'historique lisible', 'vision commerciale continue'],
                'tone' => 'ocean',
            ],
            [
                'eyebrow' => 'Facturation exploitable',
                'title' => 'Devis, factures et PDF operationnels sans sortir du flux de vente.',
                'body' => 'Preparez les documents, pilotez les statuts, exportez les PDF et gardez une trace claire des montants et des relances.',
                'points' => ['devis et factures', 'exports PDF', 'suivi de paiement', 'documents partageables'],
                'tone' => 'amber',
            ],
            [
                'eyebrow' => 'Stock et logistique',
                'title' => 'Articles, fournisseurs, commandes et bons de livraison avec tracabilite reelle.',
                'body' => 'Le stock repose sur les mouvements et sur les BL, pour une logique plus fiable, audit-safe et evolutive.',
                'points' => ['achats fournisseurs', 'BL entree et sortie', 'mouvements de stock', 'historique complet'],
                'tone' => 'emerald',
            ],
            [
                'eyebrow' => 'Coordination projet',
                'title' => 'Projets, execution et synchronisation d outils dans une meme cadence.',
                'body' => 'Conservez votre logique projet interne et ajoutez Trello, notes ou documentation sans casser l organisation existante.',
                'points' => ['pilotage projet', 'taches structurees', 'liaisons externes', 'alignement equipe'],
                'tone' => 'sky',
            ],
            [
                'eyebrow' => 'Automations utiles',
                'title' => 'Des suggestions actionnables au bon moment, sans surcharger les utilisateurs.',
                'body' => 'Le moteur propose des suites logiques vers Calendar, Gmail, Sheets, Docs ou Notion au moment ou le contexte est le plus utile.',
                'points' => ['suggestions contextuelles', 'acceptation unitaire ou globale', 'ouverture en nouvel onglet', 'reconnexion geree'],
                'tone' => 'violet',
            ],
            [
                'eyebrow' => 'Gouvernance et sauvegarde',
                'title' => 'Exports, archives ZIP, sauvegardes cloud et controle des donnees sensibles.',
                'body' => 'Protegez les donnees avec des exports structures, des sauvegardes Drive ou Dropbox et une lecture claire des operations systeme.',
                'points' => ['exports Excel', 'archives ZIP', 'sauvegardes cloud', 'notifications et suivi'],
                'tone' => 'slate',
            ],
        ]);
    }

    private function buildWorkflowSteps(): array
    {
        return [
            [
                'step' => '01',
                'title' => 'Capter',
                'body' => 'Le CRM accueille le client, la demande, le rendez-vous ou la commande dans une structure immediate et lisible.',
            ],
            [
                'step' => '02',
                'title' => 'Orchestrer',
                'body' => 'Les modules collaborent avec les integrations pour generer documents, notes, feuilles, agendas et suivis.',
            ],
            [
                'step' => '03',
                'title' => 'Tracer',
                'body' => 'Chaque mouvement important garde une reference claire: facture, BL, stock, sauvegarde, historique ou notification.',
            ],
            [
                'step' => '04',
                'title' => 'Decider',
                'body' => 'L utilisateur retrouve une vue exploitable pour vendre, livrer, facturer, documenter et relancer sans perdre le contexte.',
            ],
        ];
    }

    private function buildExtensionCategories(Collection $extensions): array
    {
        return $extensions
            ->groupBy('category_label')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'label' => (string) ($first['category_label'] ?? 'Autre'),
                    'icon' => (string) ($first['category_icon'] ?? 'fa-puzzle-piece'),
                    'color' => (string) ($first['category_color'] ?? '#64748b'),
                    'items' => $items
                        ->map(fn (array $item) => (string) ($item['name'] ?? 'Intégration'))
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function buildHighlights(): array
    {
        return [
            [
                'title' => 'Une interface pour les operations reelles',
                'body' => 'Vente, facturation, stock, documentation et synchronisation externe se parlent sans multiplier les outils disparates.',
            ],
            [
                'title' => 'Des integrations gratuites, mais utiles',
                'body' => 'Google, Dropbox, Slack, Notion ou Trello renforcent le quotidien au lieu de devenir un paywall artificiel.',
            ],
            [
                'title' => 'Un socle qui peut grandir',
                'body' => 'L architecture reste prete pour l equipe, plus d utilisateurs, plus de modules et des scenarios plus proches d un ERP leger.',
            ],
        ];
    }

    private function buildPricingPeriods(): array
    {
        $baseMonthly = 15.0;
        $periods = [
            ['months' => 1, 'discount' => 0, 'label' => '1 mois', 'badge' => 'Flexibilite'],
            ['months' => 3, 'discount' => 5, 'label' => '3 mois', 'badge' => '-5%'],
            ['months' => 6, 'discount' => 10, 'label' => '6 mois', 'badge' => '-10%'],
            ['months' => 12, 'discount' => 20, 'label' => '1 an', 'badge' => '-20%'],
        ];

        return array_map(function (array $period) use ($baseMonthly) {
            $grossTotal = $baseMonthly * $period['months'];
            $netTotal = round($grossTotal * (1 - ($period['discount'] / 100)), 2);
            $effectiveMonthly = round($netTotal / $period['months'], 2);

            return [
                'label' => $period['label'],
                'badge' => $period['badge'],
                'months' => $period['months'],
                'discount' => $period['discount'],
                'gross_total' => $grossTotal,
                'total' => $netTotal,
                'monthly' => $effectiveMonthly,
                'total_label' => $this->formatDt($netTotal),
                'monthly_label' => $this->formatDt($effectiveMonthly),
                'recommended' => $period['months'] === 12,
            ];
        }, $periods);
    }

    private function formatDt(float $amount): string
    {
        $formatted = number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2, ',', ' ');

        return $formatted . ' DT';
    }
}
