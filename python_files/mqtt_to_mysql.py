#!/usr/bin/env python3

import paho.mqtt.client as mqtt
import mysql.connector
import json
from datetime import datetime

# MQTT Konfiguration
MQTT_BROKER = "localhost"
MQTT_PORT = 1883
MQTT_TOPIC = "sensoren/raum1"
MQTT_CLIENT_ID = "mysql_logger"

# MySQL Konfiguration
MYSQL_HOST = "localhost"
MYSQL_USER = "mqtt_logger"
MYSQL_PASSWORD = "sicherespasswort"
MYSQL_DATABASE = "sensordaten"

# Globale Datenbankverbindung
db_connection = None
cursor = None

def connect_mysql():
    """Verbindung zur MySQL-Datenbank herstellen"""
    global db_connection, cursor
    try:
        db_connection = mysql.connector.connect(
            host=MYSQL_HOST,
            user=MYSQL_USER,
            password=MYSQL_PASSWORD,
            database=MYSQL_DATABASE
        )
        cursor = db_connection.cursor()
        print(f"✓ Erfolgreich mit MySQL-Datenbank verbunden: {MYSQL_DATABASE}")
    except mysql.connector.Error as err:
        print(f"✗ MySQL Verbindungsfehler: {err}")
        exit(1)

def on_connect(client, userdata, flags, rc):
    """Callback bei erfolgreicher MQTT-Verbindung"""
    if rc == 0:
        print(f"✓ Verbunden mit MQTT Broker: {MQTT_BROKER}")
        client.subscribe(MQTT_TOPIC)
        print(f"✓ Topic abonniert: {MQTT_TOPIC}")
    else:
        print(f"✗ Verbindung fehlgeschlagen mit Code: {rc}")

def on_message(client, userdata, msg):
    """Callback bei empfangener MQTT-Nachricht"""
    try:
        # JSON-Daten parsen
        payload = msg.payload.decode('utf-8')
        data = json.loads(payload)

        # Daten aus JSON extrahieren
        temperature = data.get('temperature')
        humidity = data.get('humidity')
        pressure = data.get('pressure')
        brightness = data.get('brightness')

        # In Datenbank einfügen
        sql = """INSERT INTO messwerte (temperature, humidity, pressure, brightness) 
                 VALUES (%s, %s, %s, %s)"""
        values = (temperature, humidity, pressure, brightness)

        cursor.execute(sql, values)
        db_connection.commit()

        print(f"✓ Daten gespeichert: T={temperature}°C, H={humidity}%, P={pressure}hPa, L={brightness}lux")

    except json.JSONDecodeError as e:
        print(f"✗ JSON Fehler: {e}")
    except mysql.connector.Error as e:
        print(f"✗ MySQL Fehler: {e}")
        # Verbindung wiederherstellen bei Fehler
        connect_mysql()
    except Exception as e:
        print(f"✗ Fehler: {e}")

def on_disconnect(client, userdata, rc):
    """Callback bei Verbindungsabbruch"""
    if rc != 0:
        print(f"⚠ Unerwarteter Verbindungsabbruch. Code: {rc}")

def main():
    """Hauptprogramm"""
    # MySQL-Verbindung herstellen
    connect_mysql()

    # MQTT-Client initialisieren
    mqtt_client = mqtt.Client(MQTT_CLIENT_ID)
    mqtt_client.on_connect = on_connect
    mqtt_client.on_message = on_message
    mqtt_client.on_disconnect = on_disconnect

    # Mit MQTT Broker verbinden
    try:
        mqtt_client.connect(MQTT_BROKER, MQTT_PORT, keepalive=60)
        print("\n📊 MQTT-zu-MySQL Logger gestartet...")
        print("Warte auf Sensordaten...\n")

        # Endlos-Schleife für MQTT-Empfang
        mqtt_client.loop_forever()

    except KeyboardInterrupt:
        print("\n⚠ Programm wird beendet...")
    except Exception as e:
        print(f"✗ Fehler beim Verbinden: {e}")
    finally:
        # Aufräumen
        if cursor:
            cursor.close()
        if db_connection:
            db_connection.close()
        mqtt_client.disconnect()
        print("✓ Verbindungen geschlossen")

if __name__ == "__main__":
    main()
