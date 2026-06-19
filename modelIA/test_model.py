import joblib
model = joblib.load('modele.pkl')
vec = joblib.load('vectorizer.pkl')

phrase = "Déconnexions intempestives de la borne wifi principale de l\'étage."
vec_phrase = vec.transform([phrase])
print(f"Catégorie prédite : {model.predict(vec_phrase)[0]}")