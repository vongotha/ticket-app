
  async function chargerDetailsTicket(element) {
    // 1. Gérer le style de sélection visuelle
    document.querySelectorAll('.trow').forEach(r => r.classList.remove('sel'));
    element.classList.add('sel');

    const ticketId = element.getAttribute('data-id');

    try {
      // 2. Récupérer les détails depuis ton API PHP (tu devras créer cette route)
      const response = await fetch(`/projet/ticket/ticket/details?id=${ticketId}`);
      const ticket = await response.json();

      if (response.ok) {
        // 3. Injecter les données dynamiquement dans le panneau de droite
        document.querySelector('.detail-header style').previousElementSibling.textContent = `#T-00${ticket.id}`;
        document.querySelector('.ai-box-text').innerHTML = `Catégorie détectée : <strong>${ticket.categorie}</strong>. Confiance : <strong>${ticket.score_ia}%</strong>.`;
        document.querySelector('.info-block').nextElementSibling.previousElementSibling.textContent = ticket.titre;
        // ... Répète l'opération pour la description et le demandeur
      }
    } catch (err) {
      console.error("Erreur lors du chargement du ticket", err);
    }
  }

  // Action du bouton "Marquer résolu"
  document.querySelector('.abtn-prim').addEventListener('click', async () => {
    const ticketId = document.querySelector('.trow.sel').getAttribute('data-id');
    const note = document.querySelector('textarea').value;

    const response = await fetch('/projet/ticket/ticket/resolve', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: ticketId, resolution: note })
    });

    if (response.ok) {
      alert("Ticket clôturé !");
      location.reload();
    }
  });
