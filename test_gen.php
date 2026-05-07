<?php
require_once __DIR__ . '/src/functions.php';

$salles = charger_salles(__DIR__ . '/data/salles.csv');
$promotions = charger_promotions(__DIR__ . '/data/promotions.csv');
$cours = charger_cours(__DIR__ . '/data/cours.csv');
$options = charger_options(__DIR__ . '/data/options.csv');
$creneaux = ['08h00-12h00', '13h00-17h00'];

$planning = generer_planning($salles, $promotions, $cours, $options, $creneaux);
if (sauvegarder_planning($planning, __DIR__ . '/data/planning.csv')) {
    echo "Planning generated successfully in planning.csv\n";
} else {
    echo "Failed to save planning\n";
}
?>
