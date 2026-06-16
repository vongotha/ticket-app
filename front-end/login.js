
  document.getElementById('login-btn').addEventListener('click', async (e) => {
    e.preventDefault(); // Empêche le rechargement de la page

    // 1. Récupération des champs (bien à l'intérieur de la fonction !)
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
        // 2. Envoi de la requête POST vers la route API clean
        const response = await fetch('/projet/ticket/api/login', {
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

            // 4. Redirection automatique propre selon le rôle
            if (result.user.role === 'technicien') {
                window.location.href = '/projet/ticket/dashboard-technicien';
            } else if (result.user.role === 'admin') {
                window.location.href = '/projet/ticket/dashboard-admin';
            } else {
                window.location.href = '/projet/ticket/dashboard-employe';
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