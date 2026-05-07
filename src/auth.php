<?php
/**
 * src/auth.php - Gestion de la session et protection des routes
 */
session_start();

/**
 * Vérifie si l'utilisateur est authentifié.
 * Redirige vers la page de connexion si ce n'est pas le cas.
 */
function check_auth() {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header('Location: login.php');
        exit;
    }
}
?>
