
document.getElementById('login-btn').addEventListener('click', async (e) => {
e.preventDefault(); // Empêche le rechargement de la page

// 1. Récupération des champs
const email = document.getElementById('em').value;
const password = document.getElementById('pw').value;
const errorEl = document.getElementById('error-msg');

// Réinitialiser l'affichage de l'erreur
errorEl.style.display = 'none';

if (!email || !password) {
    errorEl.textContent = "Veuillez remplir tous les champs.";
    errorEl.style.display = 'block';
    return;
}

try {
    // 2. Envoi de la requête POST vers ton routeur index.php
    const response = await fetch('/projet/ticket/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email: email, password: password })
    });

    const result = await response.json();

    if (response.ok && result.status === 'success') {
    // 3. Stocker les infos de l'utilisateur dans le navigateur
    localStorage.setItem('user', JSON.stringify(result.user));

    // 4. Redirection automatique selon le rôle reçu du Backend
    if (result.user.role === 'technicien' || result.user.role === 'admin') {
        window.location.href = 'dashboard-tech.html'; // Ajuste le nom du fichier reçu
    } else {
        window.location.href = 'dashboard-client.html'; // Ajuste le nom du fichier reçu
    }
    } else {
    // Afficher l'erreur renvoyée par AuthController.php
    errorEl.textContent = result.error || "Une erreur est survenue.";
    errorEl.style.display = 'block';
    }

} catch (error) {
    errorEl.textContent = "Impossible de joindre le serveur.";
    errorEl.style.display = 'block';
}
});