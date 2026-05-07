<?php 
/**
 * liste_salles.php - Affichage et gestion des infrastructures (Suppression & Modification)
 */
require_once __DIR__ . '/src/functions.php'; 
require_once __DIR__ . '/src/auth.php';
check_auth();

$chemin_salles = __DIR__ . '/data/salles.csv';
$message = '';
$msg_type = 'success';

// Gestion de la suppression d'une salle
if (isset($_GET['delete'])) {
    $id_a_supprimer = $_GET['delete'];
    if (supprimer_salle($id_a_supprimer, $chemin_salles)) {
        $message = "La salle $id_a_supprimer a été supprimée et le planning a été mis à jour.";
        $msg_type = 'success';
    }
}

// Gestion de la modification de capacité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_salle = $_POST['id_salle'];
    $nouvelle_capacite = $_POST['capacite'];
    if (modifier_salle($id_salle, $nouvelle_capacite, $chemin_salles)) {
        $message = "Capacité de la salle $id_salle mise à jour.";
        $msg_type = 'success';
    }
}

// Chargement des données des salles depuis le CSV
$salles = charger_salles($chemin_salles);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infrastructures - SGA</title>
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
        <a href="modifier_planning.php">Modifier</a>
        <a href="liste_salles.php" class="active">Salles</a>
        <a href="logout.php" class="logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Déconnexion
        </a>
    </nav>
</header>

<main>
    <h2>Infrastructures</h2>
    <p style="color: var(--text-muted); margin-top: -1.5rem; margin-bottom: 2rem;">Gestion des auditoires et salles de cours</p>

    <?php if ($message): ?>
    <div class="alert alert-<?=$msg_type?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/><path d="M3 9V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4"/></svg>
            Auditoires répertoriés
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Identifiant</th>
                    <th>Désignation</th>
                    <th>Capacité (étudiants)</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($salles as $s): ?>
                <tr>
                    <td><span class="badge badge-primary"><?=$s['id']?></span></td>
                    <td><strong><?=$s['designation']?></strong></td>
                    <td>
                        <form method="POST" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id_salle" value="<?=$s['id']?>">
                            <input type="number" name="capacite" value="<?=$s['capacite']?>" 
                                   style="width: 70px; padding: 4px; font-size: 0.85rem; border: 1px solid #ddd; border-radius: 4px;">
                            <button type="submit" class="btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">OK</button>
                        </form>
                    </td>
                    <td style="text-align: right;">
                        <a href="liste_salles.php?delete=<?=urlencode($s['id'])?>" 
                           onclick="return confirm('Attention : Supprimer cette salle la retirera aussi de tous les plannings. Continuer ?')" 
                           class="btn-delete" 
                           style="color: #ef4444; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 4px 8px; border: 1px solid transparent; border-radius: 4px; transition: 0.2s;">
                            Supprimer
                        </a>
                    </td>
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
