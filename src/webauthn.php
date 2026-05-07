<?php
/**
 * src/webauthn.php - Gestion simplifiée de l'authentification WebAuthn
 * Permet l'usage du capteur d'empreinte digitale via le navigateur.
 */

class WebAuthnHandler {
    private $storageFile;

    public function __construct() {
        $this->storageFile = __DIR__ . '/../data/biometrics.json';
        if (!file_exists($this->storageFile)) {
            file_put_contents($this->storageFile, json_encode([]));
        }
    }

    /**
     * Génère un défi (challenge) pour l'enregistrement ou la connexion.
     */
    public function generateChallenge() {
        return bin2hex(random_bytes(16));
    }

    /**
     * Enregistre une nouvelle clé publique biométrique pour un utilisateur.
     */
    public function registerCredential($username, $credentialId, $publicKey) {
        $data = json_decode(file_get_contents($this->storageFile), true);
        $data[$username] = [
            'id' => $credentialId,
            'publicKey' => $publicKey
        ];
        return file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Récupère la clé publique d'un utilisateur pour vérification.
     */
    public function getCredential($username) {
        $data = json_decode(file_get_contents($this->storageFile), true);
        return isset($data[$username]) ? $data[$username] : null;
    }
}
?>
