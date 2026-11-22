<?php
declare(strict_types=1);
/** API mobile lecture seule : mêmes requêtes que executive/index.php (20/11/2026). */
require_once __DIR__ . '/bootstrap.php';
requireExecutiveAuth();
function sumq(PDO $pdo,string $sql,array $params=[]): float { $s=$pdo->prepare($sql);$s->execute($params);return (float)$s->fetchColumn(); }
try {
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) $from=date('Y-m-01');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) $to=date('Y-m-d');

// Situation disponible actuelle : caisse_soldes est la source officielle des soldes caisses.
$caisseUSD = sumq($pdo,"SELECT COALESCE(SUM(solde),0) FROM caisse_soldes WHERE devise='USD'");
$caisseCDF = sumq($pdo,"SELECT COALESCE(SUM(solde),0) FROM caisse_soldes WHERE devise='CDF'");
$banqueUSD = sumq($pdo,"SELECT COALESCE(SUM(CASE WHEN type_mouvement='DEPOT' THEN montant WHEN type_mouvement='RETRAIT' THEN -montant ELSE 0 END),0) FROM mouvements_bancaires WHERE devise='USD'");
$banqueCDF = sumq($pdo,"SELECT COALESCE(SUM(CASE WHEN type_mouvement='DEPOT' THEN montant WHEN type_mouvement='RETRAIT' THEN -montant ELSE 0 END),0) FROM mouvements_bancaires WHERE devise='CDF'");
$tresUSD = $caisseUSD + $banqueUSD;
$tresCDF = $caisseCDF + $banqueCDF;

// Approvisionnements directs confirmés : affichés séparément des APPORT existants.
$directUSD = sumq($pdo,"SELECT COALESCE(SUM(montant),0) FROM approvisionnements_directs WHERE statut='CONFIRME' AND devise='USD' AND date_reception BETWEEN ? AND ?",[$from,$to]);
$directCDF = sumq($pdo,"SELECT COALESCE(SUM(montant),0) FROM approvisionnements_directs WHERE statut='CONFIRME' AND devise='CDF' AND date_reception BETWEEN ? AND ?",[$from,$to]);

// Indicateurs de la période sélectionnée.
$appUSD = sumq($pdo,"SELECT COALESCE(SUM(montant),0) FROM encaissements WHERE devise='USD' AND date_encaissement BETWEEN ? AND ?",[$from,$to]);
$appCDF = sumq($pdo,"SELECT COALESCE(SUM(montant),0) FROM encaissements WHERE devise='CDF' AND date_encaissement BETWEEN ? AND ?",[$from,$to]);
$decUSD = sumq($pdo,"SELECT COALESCE(SUM(montant),0) FROM decaissements WHERE devise='USD' AND date_decaissement BETWEEN ? AND ?",[$from,$to]);
$decCDF = sumq($pdo,"SELECT COALESCE(SUM(montant),0) FROM decaissements WHERE devise='CDF' AND date_decaissement BETWEEN ? AND ?",[$from,$to]);

$sites = $pdo->prepare("\n    SELECT s.id, s.nom_site,\n           COALESCE(SUM(CASE WHEN cs.devise='USD' THEN cs.solde ELSE 0 END),0) solde_usd,\n           COALESCE(SUM(CASE WHEN cs.devise='CDF' THEN cs.solde ELSE 0 END),0) solde_cdf\n    FROM sites s\n    LEFT JOIN caisses c ON c.site_id=s.id AND c.statut='ACTIVE'\n    LEFT JOIN caisse_soldes cs ON cs.caisse_id=c.id\n    GROUP BY s.id,s.nom_site\n    ORDER BY s.nom_site\n");
$sites->execute();
$siteRows=$sites->fetchAll(PDO::FETCH_ASSOC);

// Evolution sur les 6 derniers mois : apports BOSS et décaissements, séparés par devise.
$months=[];
for($i=5;$i>=0;$i--){
    $ts=strtotime(date('Y-m-01')." -$i months");
    $key=date('Y-m',$ts);
    $months[$key]=['label'=>date('m/Y',$ts),'app_usd'=>0,'app_cdf'=>0,'dec_usd'=>0,'dec_cdf'=>0];
}
$start6=array_key_first($months).'-01';
$st=$pdo->prepare("SELECT DATE_FORMAT(date_encaissement,'%Y-%m') ym, devise, SUM(montant) total FROM encaissements WHERE date_encaissement>=? GROUP BY ym,devise");
$st->execute([$start6]);
foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){ if(isset($months[$r['ym']])) $months[$r['ym']]['app_'.strtolower($r['devise'])]=(float)$r['total']; }
$st=$pdo->prepare("SELECT DATE_FORMAT(date_decaissement,'%Y-%m') ym, devise, SUM(montant) total FROM decaissements WHERE date_decaissement>=? GROUP BY ym,devise");
$st->execute([$start6]);
foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){ if(isset($months[$r['ym']])) $months[$r['ym']]['dec_'.strtolower($r['devise'])]=(float)$r['total']; }
$maxUSD=max(1,max(array_map(fn($m)=>max($m['app_usd'],$m['dec_usd']),$months)));
$maxCDF=max(1,max(array_map(fn($m)=>max($m['app_cdf'],$m['dec_cdf']),$months)));

// Dépenses par site sur la période choisie.
$st=$pdo->prepare("SELECT COALESCE(s.id,0) site_id, COALESCE(s.nom_site,'Global / non rattaché') nom_site, SUM(CASE WHEN d.devise='USD' THEN d.montant ELSE 0 END) usd, SUM(CASE WHEN d.devise='CDF' THEN d.montant ELSE 0 END) cdf FROM decaissements d LEFT JOIN sites s ON s.id=d.site_id WHERE d.date_decaissement BETWEEN ? AND ? GROUP BY s.id,s.nom_site ORDER BY usd DESC,cdf DESC");
$st->execute([$from,$to]);
$depSites=$st->fetchAll(PDO::FETCH_ASSOC);
$maxSiteUSD=max(1,...array_map(fn($r)=>(float)$r['usd'],$depSites ?: [['usd'=>0]]));
$maxSiteCDF=max(1,...array_map(fn($r)=>(float)$r['cdf'],$depSites ?: [['cdf'=>0]]));


jsonResponse(['success'=>true,'data'=>[
  'periode'=>['from'=>$from,'to'=>$to],
  'tresorerie'=>['USD'=>$tresUSD,'CDF'=>$tresCDF],
  'caisses'=>['USD'=>$caisseUSD,'CDF'=>$caisseCDF],
  'banques'=>['USD'=>$banqueUSD,'CDF'=>$banqueCDF],
  'entrees'=>['USD'=>$appUSD,'CDF'=>$appCDF],
  'depenses'=>['USD'=>$decUSD,'CDF'=>$decCDF],
  'approvisionnements_directs'=>['USD'=>$directUSD,'CDF'=>$directCDF],
  'sites'=>$siteRows,'evolution'=>array_values($months),'depenses_sites'=>$depSites
]]);
} catch(Throwable $e){safeError($e);}
