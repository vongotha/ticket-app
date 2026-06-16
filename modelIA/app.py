from flask import Flask, request, jsonify
import joblib

app = Flask(__name__)
model = joblib.load("modele.pkl")
vectorizer = joblib.load("vectorizer.pkl")

@app.route("/predict", methods=["POST"])
def predict():
    data = request.json
    texte = data["ticket"]
    
    x = vectorizer.transform([texte])
    categorie = model.predict(x)[0]
    return jsonify({
        "categorie": categorie
    })

if __name__== "__main__":
    app.run(debug=True)    