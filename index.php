<?php 
require_once __DIR__ . '/src/functions.php'; 
require_once __DIR__ . '/src/auth.php';
check_auth();

$planning_file = __DIR__ . '/data/planning.csv';
$salles = charger_salles(__DIR__ . '/data/salles.csv');
$promotions = charger_promotions(__DIR__ . '/data/promotions.csv');
$cours = charger_cours(__DIR__ . '/data/cours.csv');
$options = charger_options(__DIR__ . '/data/options.csv');
$creneaux = ['08h00-12h00', '13h00-17h00'];

// Logique : charger existant ou générer un nouveau planning
$rapport_stats = [];
if (isset($_GET['regeneration'])) {
    $planning = generer_planning($salles, $promotions, $cours, $options, $creneaux);
    sauvegarder_planning($planning, $planning_file);
    header('Location: index.php?msg=regenerated');
    exit;
} elseif (isset($_GET['rapport'])) {
    $planning = charger_planning($planning_file);
    $rapport_stats = generer_rapport_occupation($planning_file, $salles);
} else {
    $planning = charger_planning($planning_file);
    if (!$planning) {
        $planning = generer_planning($salles, $promotions, $cours, $options, $creneaux);
        sauvegarder_planning($planning, $planning_file);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Accueil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/><path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/><path d="M13 13h4"/><path d="M13 17h4"/><path d="M7 13h2v4H7z"/></svg>
        SGA
    </h1>
    <nav>
        <a href="index.php" class="active">Planning</a>
        <a href="saisie.php">Saisie</a>
        <a href="modifier_planning.php">Modifier</a>
        <a href="liste_salles.php">Salles</a>
        <a href="logout.php" class="logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Déconnexion
        </a>
    </nav>
</header>

<main>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2>Tableau de bord</h2>
            <p style="color: var(--text-muted); margin-top: -1.5rem;">Planning hebdomadaire par promotion</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="index.php?rapport=1" class="button btn-secondary" style="background: rgba(59, 130, 246, 0.1); color: var(--primary); border-color: var(--primary);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Rapport d'occupation
            </a>
            <a href="index.php?regeneration=1" class="button btn-secondary" onclick="return confirm('Êtes-vous sûr de vouloir régénérer tout le planning ?')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                Régénérer le planning
            </a>
        </div>
    </div>

    <?php if (!empty($rapport_stats)): ?>
    <div class="card" style="margin-bottom: 2rem; border-left: 4px solid var(--primary);">
        <h3 style="margin-top: 0;">Statistiques d'occupation hebdomadaire</h3>
        <p style="font-size: 0.9rem; color: var(--text-muted);">Le fichier <code>data/rapport_occupation.txt</code> a été mis à jour.</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <?php foreach ($rapport_stats as $stat): ?>
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; font-size: 0.9rem; border: 1px solid #e2e8f0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; color: var(--primary);"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo $stat; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'regenerated'): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        Le planning a été régénéré avec succès !
    </div>
    <?php endif; ?>

    <div class="planning-container">
        <?php echo afficher_planning_html($planning, $cours, $salles); ?>
    </div>
</main>

<footer>
    UPC — Faculté des Sciences Informatiques &copy; 2025-2026<br>
    <small style="color: var(--text-muted); opacity: 0.6;">Système de Gestion des Auditoires</small>
</footer>
    <script src="script.js"></script>
</body>
</html>
