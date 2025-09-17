# 🏠 Smarthome Proof of Concept auf Raspberry Pi

Dieses Repository enthält den Quellcode und die Dokumentation eines Smarthome-Prototyps, der auf einem Raspberry Pi 5 basiert.  
Ziel des Projekts ist es, verschiedene Umweltdaten wie Temperatur, Luftfeuchtigkeit, Helligkeit und Luftqualität mithilfe von Sensoren zu erfassen und diese in einem lokalen Netzwerk über ein Webinterface darzustellen.  

---

## 📌 Features
- 🌡️ Erfassen von Umweltdaten (Temperatur, Luftfeuchtigkeit, Luftdruck, Luftqualität, Helligkeit)  
- 🐍 Sensor-Ansteuerung über **Python**  
- 💾 Speicherung der Messwerte (CSV oder SQLite)  
- 🌐 **Symfony-Weboberfläche** zur Anzeige im lokalen Netzwerk  
- 🔄 API-Schnittstelle für den Datenaustausch zwischen Python und Symfony  
- 📈 Visualisierung der Werte im Browser  

---

## 🛠️ Technologien
- **Hardware:** Raspberry Pi 5, Sensoren (BME280, MQ-135, BH1750 etc.)  
- **Sprachen:** Python 3, PHP 8  
- **Frameworks:** Symfony, Bootstrap (Frontend)  
- **Datenhaltung:** SQLite oder CSV  
- **Tools:** GitHub, VS Code, Git  

---

## 📂 Projektstruktur

```bash
.
├── sensor/               # Python-Skripte für Sensoren
│   ├── temperature.py
│   ├── humidity.py
│   ├── light.py
│   └── ...
├── webapp/               # Symfony Webanwendung
│   ├── config/
│   ├── public/
│   ├── src/
│   └── templates/
├── data/                 # Gespeicherte CSV/SQLite Daten
│   └── logs.csv
├── docs/                 # Dokumentation & Projektantrag
│   └── Projektantrag.md
├── README.md             # Dieses Dokument
└── requirements.txt      # Python Abhängigkeiten
