<?php

namespace Vendor\CrmCore\Traits;

use Illuminate\Support\Str;

/**
 * Expose un identifiant public UUID (colonne `uuid`) tout en CONSERVANT la clé
 * primaire numérique interne (`id`) et toutes les relations SQL existantes.
 *
 * Objectifs :
 *  - Les URLs, route(), formulaires, AJAX et redirections utilisent l'UUID.
 *  - Les IDs internes restent inchangés pour les traitements internes / SQL.
 *  - Réutilisable par le core, les packages, les extensions et les futurs modules
 *    (il suffit d'ajouter `use HasPublicUuid;` au modèle + la colonne `uuid`).
 *
 * Sécurité / robustesse :
 *  - getRouteKey() retombe sur l'ID numérique si l'UUID est absent (colonne pas
 *    encore migrée, ligne non backfillée) => aucun lien cassé, en toute circonstance.
 *  - resolveRouteBinding() accepte l'UUID public OU l'ancien ID numérique
 *    (double résolution / rétrocompatibilité des anciennes URLs et intégrations).
 *  - La résolution passe par newQuery(), donc les global scopes (isolation tenant,
 *    soft-deletes) et les policies restent pleinement appliqués : l'UUID n'est PAS
 *    une protection d'accès, il ne contourne aucun contrôle.
 */
trait HasPublicUuid
{
    public static function bootHasPublicUuid(): void
    {
        static::creating(function ($model): void {
            $column = $model->getUuidColumn();
            if (empty($model->{$column})) {
                $model->{$column} = (string) Str::uuid();
            }
        });
    }

    /**
     * Nom de la colonne UUID (surchargeable via `const UUID_COLUMN`).
     */
    public function getUuidColumn(): string
    {
        return defined(static::class . '::UUID_COLUMN') ? static::UUID_COLUMN : 'uuid';
    }

    /**
     * Le route-model binding utilise l'UUID par défaut.
     */
    public function getRouteKeyName(): string
    {
        return $this->getUuidColumn();
    }

    /**
     * Valeur exposée dans les URLs générées : UUID si disponible, sinon repli sur
     * l'ID numérique (jamais de lien vide même avant la migration/backfill).
     */
    public function getRouteKey()
    {
        $uuid = $this->getAttribute($this->getUuidColumn());

        return !empty($uuid) ? $uuid : $this->getAttribute($this->getKeyName());
    }

    /**
     * Résolution du binding : UUID public OU ancien ID numérique (rétrocompat).
     * Passe par newQuery() => global scopes (tenant), soft-deletes et policies
     * restent appliqués.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        // Rétrocompatibilité : un ancien identifiant numérique reste résolu.
        if ($field === $this->getUuidColumn() && ctype_digit((string) $value)) {
            $field = $this->getKeyName();
        }

        return $this->newQuery()->where($field, $value)->first();
    }
}
