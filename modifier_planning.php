<?php
/**
 * modifier_planning.php - Gestion manuelle du planning avec vérification des contraintes
 */
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/auth.php';
check_auth();

// Chemins des fichiers de données
$planning_file = __DIR__ . '/data/planning.csv';
$salles = charger_salles(__DIR__ . '/data/salles.csv');
$promotions = charger_promotions(__DIR__ . '/data/promotions.csv');
$cours = charger_cours(__DIR__ . '/data/cours.csv');
$planning = charger_planning($planning_file);

// Carte des cours pour affichage
$cours_map = [];
foreach($cours as $c) $cours_map[$c['id']] = $c['intitule'];

$message = '';
$msg_type = 'success';

// Traitement de la mise à jour manuelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $index = (int)$_POST['index'];
    $new_salle = $_POST['new_salle'];
    $new_creneau = $_POST['new_creneau'];

    // Récupération de l'effectif de la promotion concernée
    $effectif = 0;
    foreach($promotions as $p) { 
        if($p['id'] == $planning[$index]['id_groupe']) $effectif = $p['effectif']; 
    }

    // Validation des contraintes métier
    if (!capacite_suffisante($salles, $new_salle, $effectif)) {
        $message = "Erreur: Capacité insuffisante pour cette promotion.";
        $msg_type = 'error';
    } elseif (!salle_disponible($planning, $new_salle, $planning[$index]['jour'], $new_creneau)) {
        $message = "Erreur: Cette salle est déjà occupée sur ce créneau.";
        $msg_type = 'error';
    } else {
        // Mise à jour de l'affectation
        $planning[$index]['id_salle'] = $new_salle;
        $planning[$index]['creneau'] = $new_creneau;
        
        if (sauvegarder_planning($planning, $planning_file)) {
            $message = "Le planning a été mis à jour avec succès.";
            $msg_type = 'success';
        } else {
            $message = "Erreur lors de la sauvegarde du fichier.";
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Planning - SGA</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/><path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/><path d="M13 13h4"/><path d="M13 17h4"/><path d="M7 13h2v4H7z"/></svg>
        SGA
    </h1>
    <nav>
        <a href="index.php">Planning</a>
        <a href="saisie.php">Saisie</a>
        <a href="modifier_planning.php" class="active">Modifier</a>
        <a href="liste_salles.php">Salles</a>
        <a href="logout.php" class="logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Déconnexion
        </a>
    </nav>
</header>

<main>
    <h2>Ajustements</h2>
    <p style="color: var(--text-muted); margin-top: -1.5rem; margin-bottom: 2rem;">Modification manuelle des affectations de salles</p>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $msg_type; ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <?php if ($msg_type === 'success'): ?>
                <polyline points="20 6 9 17 4 12"/>
            <?php else: ?>
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            <?php endif; ?>
        </svg>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 5-3 3 1 1 3-3z"/><path d="M15 8 8 15"/><path d="M21 3 5 19l-2 2h4l16-16z"/><path d="M19 11V9"/></svg>
            Édition des créneaux
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Jour & Cours</th>
                    <th>Groupe</th>
                    <th>Nouvelle Salle</th>
                    <th>Nouveau Créneau</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($planning as $i => $p): ?>
                <tr>
                    <form method="POST">
                        <td>
                            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;"><?php echo $p['jour']; ?></div>
                            <strong><?php echo $cours_map[$p['id_cours']] ?? $p['id_cours']; ?></strong>
                        </td>
                        <td><span class="badge badge-secondary"><?php echo $p['id_groupe']; ?></span></td>
                        <td>
                            <select name="new_salle" style="padding: 0.4rem; font-size: 0.85rem;">
                                <?php foreach($salles as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php if($s['id'] == $p['id_salle']) echo 'selected'; ?>><?php echo $s['id']; ?> (Cap. <?php echo $s['capacite']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="new_creneau" style="padding: 0.4rem; font-size: 0.85rem;">
                                <option value="08h00-12h00" <?php if($p['creneau'] == '08h00-12h00') echo 'selected'; ?>>08h00-12h00</option>
                                <option value="13h00-17h00" <?php if($p['creneau'] == '13h00-17h00') echo 'selected'; ?>>13h00-17h00</option>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="index" value="<?php echo $i; ?>">
                            <button type="submit" class="btn-sm">Mettre à jour</button>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<footer>
    UPC — Faculté des Sciences Informatiques &copy; 2025-2026<br>
    <small style="color: var(--text-muted); opacity: 0.6;">Système de Gestion des Auditoires</small>
</footer>
    <script src="script.js"></script>
</body>
</html>
