# app.py (Extrait du début)
from flask import Flask, request, jsonify
import joblib  # <-- On remplace pickle par joblib
import numpy as np
import os

app = Flask(__name__)

model = None
vectorizer = None

try:
    # On charge avec joblib !

    model = joblib.load('modele.pkl')
    vectorizer = joblib.load('vectorizer.pkl')
    print("🤖 Modèle IA et Vectorizer chargés avec succès via Joblib !")
except Exception as e:
    print(f"❌ ERREUR CRITIQUE AU DÉMARRAGE : {e}")

@app.route('/predict', methods=['POST'])
def predict():
    # Sécurité si les fichiers n'ont pas pu charger au démarrage
    if model is None or vectorizer is None:
        return jsonify({
            'status': 'error', 
            'message': 'Le modèle IA n\'est pas initialisé sur le serveur. Vérifiez les logs de démarrage.'
        }), 500

    data = request.get_json()
    if not data or 'description' not in data:
        return jsonify({'error': 'Description manquante'}), 400
    
    text_input = data['description']
    print(f"\n📥 TEXTE REÇU DE PHP : '{text_input}'")
    
    try:
        text_vectorized = vectorizer.transform([text_input])
        prediction = model.predict(text_vectorized)[0]
        
        if hasattr(model, "predict_proba"):
            probabilities = model.predict_proba(text_vectorized)[0]
            score_ia = int(np.max(probabilities) * 100)
        else:
            score_ia = 100
            
        print(f"🔮 PRÉDICTION IA : Categorie = {prediction} | Confiance = {score_ia}%")
        return jsonify({
            'status': 'success',
            'categorie': str(prediction),
            'score_ia': score_ia
        })
        
    except Exception as e:
        print(f"❌ ERREUR PENDANT LA PRÉDICTION : {str(e)}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)