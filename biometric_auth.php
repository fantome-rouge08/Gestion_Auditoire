<?php
/**
 * biometric_auth.php - Point d'entrée pour les requêtes AJAX WebAuthn
 */
require_once __DIR__ . '/src/webauthn.php';
require_once __DIR__ . '/src/auth.php';

header('Content-Type: application/json');

$handler = new WebAuthnHandler();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_registration_options') {
    // Préparer les options pour enregistrer une empreinte
    echo json_encode([
        'challenge' => $handler->generateChallenge(),
        'rp' => ['name' => 'SGA UPC', 'id' => 'localhost'],
        'user' => [
            'id' => bin2hex('admin'),
            'name' => 'admin',
            'displayName' => 'Administrateur SGA'
        ],
        'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7]]
    ]);
} 

elseif ($action === 'register') {
    // Enregistrer la clé publique reçue du navigateur
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['id']) && isset($input['publicKey'])) {
        $handler->registerCredential('admin', $input['id'], $input['publicKey']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
    }
}

elseif ($action === 'get_login_options') {
    // Préparer le défi pour la connexion
    $cred = $handler->getCredential('admin');
    if ($cred) {
        echo json_encode([
            'challenge' => $handler->generateChallenge(),
            // On envoie l'ID tel quel (Base64) pour que le JS le décode correctement
            'allowCredentials' => [['type' => 'public-key', 'id' => $cred['id']]]
        ]);
    } else {
        echo json_encode(['error' => 'Aucune empreinte enregistrée']);
    }
}

elseif ($action === 'verify_login') {
    // Vérification finale pour le 2FA
    if (isset($_SESSION['partial_auth']) && $_SESSION['partial_auth'] === true) {
        $_SESSION['authenticated'] = true;
        unset($_SESSION['partial_auth']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Le mot de passe doit être validé d\'abord.']);
    }
}
?>
