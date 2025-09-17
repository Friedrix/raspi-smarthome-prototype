# Smarthome Proof of Concept auf Raspberry Pi

Dieses Repository enthält den Quellcode und die Dokumentation eines Smarthome-Prototyps, der auf einem Raspberry Pi 5 basiert. Ziel des Projekts ist es, verschiedene Umweltdaten wie Temperatur, Luftfeuchtigkeit, Helligkeit und Luftqualität mithilfe von Sensoren zu erfassen und diese in einem lokalen Netzwerk über ein Webinterface darzustellen.

## 🔧 Projektübersicht

- 🧠 Steuerzentrale: Raspberry Pi 5
- 🌡️ Sensoren: Temperatur, Feuchtigkeit, Luftdruck, Helligkeit, Luftqualität (VOCs)
- 🐍 Programmiersprache: Python 3
- 🌐 Weboberfläche: Flask (lokal gehostet)
- 💾 Datenspeicherung: CSV oder SQLite
- 📶 Netzwerk: Lokales WLAN (offline)
- 🛠️ Ziel: Vollständig autarker Smarthome-Prototyp

## 📁 Projektstruktur

```bash
.
├── sensor/
│   ├── temperature.py
│   ├── humidity.py
│   └── ...
├── webapp/
│   ├── app.py
│   ├── templates/
│   └── static/
├── data/
│   └── logs.csv
├── docs/
│   └── projektbeschreibung.pdf
├── main.py
└── README.md
