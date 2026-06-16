document.querySelector('.sbtn').addEventListener('click', async (e) => {
  e.preventDefault();

  const obj = document.getElementById('tobj').value;
  const desc = document.getElementById('tdesc2').value;
  const prio = document.getElementById('tprio').value; // Récupère la priorité (Faible, Normale, Haute, Urgente)

  if (!obj || !desc) {
    alert("Veuillez remplir l'objet et la description.");
    return;
  }

  try {
    // ⚠️ LA CORRECTION EST ICI : Ajout de /api/ dans l'URL
    const response = await fetch('/projet/ticket/api/ticket/create', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json' 
      },
      body: JSON.stringify({ 
        titre: obj, 
        description: desc, 
        priorite: prio 
      })
    });

    const res = await response.json();
    
    if (response.ok) {
      alert("Ticket créé avec succès ! L'IA va maintenant l'analyser.");
      location.reload(); // Recharge la page pour rafraîchir la liste des tickets
    } else {
      alert("Erreur du serveur : " + res.message);
    }
  } catch (err) {
    alert("Impossible de joindre le serveur.");
  }
});