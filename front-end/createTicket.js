
  // Gestion de la soumission d'un nouveau ticket
  document.querySelector('.sbtn').addEventListener('click', async (e) => {
    e.preventDefault();

    const obj = document.getElementById('tobj').value;
    const desc = document.getElementById('tdesc2').value;
    const prio = document.getElementById('tprio').value;

    if (!obj || !desc) {
      alert("Veuillez remplir l'objet et la description.");
      return;
    }

    try {
      const response = await fetch('/projet/ticket/ticket/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ titre: obj, description: desc, priorite: prio })
      });

      const res = await response.json();
      if (response.ok) {
        alert("Ticket créé avec succès ! L'analyse de l'IA est en cours.");
        location.reload(); // Actualise pour voir le nouveau ticket dans la liste
      } else {
        alert("Erreur : " + res.message);
      }
    } catch (err) {
      alert("Impossible de joindre le serveur.");
    }
  });