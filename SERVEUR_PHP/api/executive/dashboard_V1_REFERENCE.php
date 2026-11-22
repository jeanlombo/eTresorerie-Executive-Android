<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireExecutiveAuth();

try {
    /*
     * Les formules ci-dessous reprennent volontairement
     * la logique du dashboard eTrésorerie existant :
     *
     * Caisse = Encaissements + Retraits bancaires - Décaissements
     * Banque = Dépôts bancaires - Retraits bancaires
     * Total  = Caisse + Banque
     */

    $data = [];

    foreach (['CDF', 'USD'] as $devise) {
        $encaissements = fetchSum(
            $pdo,
            'SELECT COALESCE(SUM(montant), 0)
             FROM encaissements
             WHERE devise = ?',
            [$devise]
        );

        $depots = fetchSum(
            $pdo,
            'SELECT COALESCE(SUM(montant), 0)
             FROM mouvements_bancaires
             WHERE devise = ?
               AND type_mouvement = ?',
            [$devise, 'DEPOT']
        );

        $retraits = fetchSum(
            $pdo,
            'SELECT COALESCE(SUM(montant), 0)
             FROM mouvements_bancaires
             WHERE devise = ?
               AND type_mouvement = ?',
            [$devise, 'RETRAIT']
        );

        $decaissements = fetchSum(
            $pdo,
            'SELECT COALESCE(SUM(montant), 0)
             FROM decaissements
             WHERE devise = ?',
            [$devise]
        );

        $soldeCaisse = $encaissements + $retraits - $decaissements;
        $soldeBanque = $depots - $retraits;
        $tresorerieTotale = $soldeCaisse + $soldeBanque;

        $data[$devise] = [
            'encaissements' => $encaissements,
            'depots_bancaires' => $depots,
            'retraits_bancaires' => $retraits,
            'decaissements' => $decaissements,
            'solde_caisse' => $soldeCaisse,
            'solde_banque' => $soldeBanque,
            'tresorerie_totale' => $tresorerieTotale
        ];
    }

    $demandesAttente = fetchCount(
        $pdo,
        "SELECT COUNT(*)
         FROM demandes_fonds
         WHERE statut = 'EN_ATTENTE'"
    );

    $decaissementsAujourdhui = [];
    foreach (['CDF', 'USD'] as $devise) {
        $decaissementsAujourdhui[$devise] = fetchSum(
            $pdo,
            'SELECT COALESCE(SUM(montant), 0)
             FROM decaissements
             WHERE devise = ?
               AND date_decaissement = CURDATE()',
            [$devise]
        );
    }

    $decaissementsMois = [];
    foreach (['CDF', 'USD'] as $devise) {
        $decaissementsMois[$devise] = fetchSum(
            $pdo,
            'SELECT COALESCE(SUM(montant), 0)
             FROM decaissements
             WHERE devise = ?
               AND YEAR(date_decaissement) = YEAR(CURDATE())
               AND MONTH(date_decaissement) = MONTH(CURDATE())',
            [$devise]
        );
    }

    jsonResponse([
        'success' => true,
        'generated_at' => date('c'),
        'data' => [
            'soldes' => $data,
            'decaissements_aujourdhui' => $decaissementsAujourdhui,
            'decaissements_mois' => $decaissementsMois,
            'demandes_en_attente' => $demandesAttente
        ]
    ]);
} catch (Throwable $e) {
    safeError($e);
}
