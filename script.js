/**
 * script.js - Logique d'interface pour le SGA (Version Allégée)
 */

// ===== Effet de Particules =====
function initParticles() {
    const container = document.getElementById('particles');
    if (!container) return;

    const count = 30;
    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.classList.add('particle');
        p.style.left = Math.random() * 100 + '%';
        p.style.width = p.style.height = (Math.random() * 3 + 1) + 'px';
        p.style.animationDuration = (Math.random() * 10 + 8) + 's';
        p.style.animationDelay = (Math.random() * 10) + 's';
        p.style.opacity = Math.random() * 0.5 + 0.1;
        container.appendChild(p);
    }
}

// ===== Gestion du Login =====
function initLogin() {
    const loginForm = document.querySelector('.login-form');
    if (!loginForm) return;

    const passwordInput = document.getElementById('password');
    // On peut ajouter ici des animations ou des validations si besoin
}

// Initialisation globale
document.addEventListener('DOMContentLoaded', () => {
    initParticles();
    initLogin();
});
