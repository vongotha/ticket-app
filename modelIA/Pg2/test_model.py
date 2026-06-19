import joblib
#Charger le model et le vectorizer
model = joblib.load("modele.pkl")
vectorizer = joblib.load("vectorizer.pkl")

#Demander un ticker à l'utilisateur
ticket = input("Décrivez votre problème : ")

#Transformer le texte
x = vectorizer.transform([ticket])

#Faire la prédiction
prediction = model.predict(x)

#print Afficher le resultat
print("categorie :", prediction[0])