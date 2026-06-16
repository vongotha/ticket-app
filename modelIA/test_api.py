import requests

url = "http://127.0.0.1:5000/predict"

data = {
    "ticket": "Je ne peux plus me connecter au WiFi"
}

response = requests.post(url, json=data)

print(response.json())