<?php
/**
 * saisie.php - Formulaires d'administration
 */
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/auth.php';
check_auth();

/**
 * Sauvegarde une nouvelle ligne dans un fichier CSV.
 */
function sauvegarder_donnee_csv($chemin_fichier, $ligne) {
    $f = fopen($chemin_fichier, 'a'); // Mode ajout
    $resultat = fputcsv($f, $ligne);
    fclose($f);
    return $resultat !== false;
}

$message = '';
// Gestion de la soumission des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $fichier = __DIR__ . '/data/' . $type . '.csv';

    if ($type === 'salles') {
        $ligne = [$_POST['id'], $_POST['designation'], $_POST['capacite']];
    } elseif ($type === 'promotions') {
        $ligne = [$_POST['id'], $_POST['libelle'], $_POST['effectif']];
    } else {
        $ligne = [];
    }

    if (!empty($ligne) && sauvegarder_donnee_csv($fichier, $ligne)) {
        $message = "Donnée ajoutée avec succès au format CSV !";
        $msg_type = "success";
    } else {
        $message = "Erreur lors de la sauvegarde.";
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie - SGA</title>
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
        <a href="saisie.php" class="active">Saisie</a>
        <a href="modifier_planning.php">Modifier</a>
        <a href="liste_salles.php">Salles</a>
        <a href="logout.php" class="logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Déconnexion
        </a>
    </nav>
</header>

<main>
    <h2>Administration</h2>
    <p style="color: var(--text-muted); margin-top: -1.5rem; margin-bottom: 2rem;">Gestion des ressources et des promotions</p>

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

    <div class="form-section">
        <div class="card">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/><path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/></svg>
                Ajouter une Salle
            </h3>
            <form method="POST">
                <input type="hidden" name="type" value="salles">
                <div class="form-group">
                    <label>Identifiant</label>
                    <input type="text" name="id" placeholder="ex: AUD-L5" required>
                </div>
                <div class="form-group">
                    <label>Désignation</label>
                    <input type="text" name="designation" placeholder="Nom de la salle" required>
                </div>
                <div class="form-group">
                    <label>Capacité</label>
                    <input type="number" name="capacite" placeholder="Nombre de places" required>
                </div>
                <button type="submit">Ajouter la salle</button>
            </form>
        </div>

        <div class="card">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Ajouter une Promotion
            </h3>
            <form method="POST">
                <input type="hidden" name="type" value="promotions">
                <div class="form-group">
                    <label>Identifiant</label>
                    <input type="text" name="id" placeholder="ex: L5" required>
                </div>
                <div class="form-group">
                    <label>Libellé</label>
                    <input type="text" name="libelle" placeholder="Nom de la promo" required>
                </div>
                <div class="form-group">
                    <label>Effectif</label>
                    <input type="number" name="effectif" placeholder="Nombre d'étudiants" required>
                </div>
                <button type="submit" style="background: var(--secondary);">Ajouter la promotion</button>
            </form>
        </div>
    </div>
</main>

<footer>
    UPC — Faculté des Sciences Informatiques &copy; 2025-2026<br>
    <small style="color: var(--text-muted); opacity: 0.6;">Système de Gestion des Auditoires</small>
</footer>
    <script src="script.js"></script>
</body>
</html>
