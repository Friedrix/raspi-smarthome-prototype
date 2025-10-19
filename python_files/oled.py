#!/usr/bin/env python3

from time import sleep
import smbus2
import bme280
import paho.mqtt.client as mqtt
import json
from PIL import ImageFont
from luma.core.interface.serial import i2c
from luma.core.render import canvas
from luma.oled.device import ssd1306
from python_tsl2591 import tsl2591

# MQTT Konfiguration
MQTT_BROKER = "188.245.217.63"  # IP des MQTT Brokers
MQTT_PORT = 1883
MQTT_TOPIC = "sensoren/raum1"
MQTT_CLIENT_ID = "raspberry_pi_sensors"

# I2C: BME280 auf 0x77
bus = smbus2.SMBus(1)
bme_addr = 0x77

# BME280 Kalibrierungsparameter laden
calibration_params = bme280.load_calibration_params(bus, bme_addr)

# TSL2591 initialisieren
tsl = tsl2591()

# OLED @ 0x3C initialisieren
serial = i2c(port=1, address=0x3C)
device = ssd1306(serial, width=128, height=64)

# Font
font_path = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
try:
    font_big = ImageFont.truetype(font_path, size=18)
except Exception:
    from PIL import ImageFont as IF
    font_big = IF.load_default()

# Callback für erfolgreiche MQTT-Verbindung (paho-mqtt 2.0+)
def on_connect(client, userdata, flags, reason_code, properties):
    if reason_code == 0:
        print("✓ Erfolgreich mit MQTT Broker verbunden")
    else:
        print(f"✗ Verbindung fehlgeschlagen: {reason_code}")

# MQTT Client initialisieren (paho-mqtt 2.0+)
mqtt_client = mqtt.Client(
    mqtt.CallbackAPIVersion.VERSION2,
    MQTT_CLIENT_ID
)
mqtt_client.on_connect = on_connect
mqtt_client.connect(MQTT_BROKER, MQTT_PORT, 60)
mqtt_client.loop_start()

def show_values(temp_c, hum_pct, press_hpa, lux, font=None):
    with canvas(device) as draw:
        y = 0
        draw.text((0, y), f"{temp_c:.1f} C", fill=255, font=font)
        y += (font.size + 2 if hasattr(font, "size") else 12)
        draw.text((0, y), f"{hum_pct:.1f} %", fill=255, font=font)
        y += (font.size + 2 if hasattr(font, "size") else 12)
        draw.text((0, y), f"{press_hpa:.1f} hPa", fill=255, font=font)
        y += (font.size + 2 if hasattr(font, "size") else 12)
        draw.text((0, y), f"{lux:.1f} lux", fill=255, font=font)

# Hauptschleife
try:
    print("✓ Sensor-Logging gestartet...")
    while True:
        # BME280 auslesen
        reading = bme280.sample(bus, bme_addr, calibration_params)
        t = reading.temperature
        h = reading.humidity
        p = reading.pressure
        
        # TSL2591 auslesen
        full, ir = tsl.get_full_luminosity()
        lux = tsl.calculate_lux(full, ir)
        
        # Daten als JSON per MQTT senden
        data = {
            'temperature': round(t, 2),
            'humidity': round(h, 2),
            'pressure': round(p, 2),
            'brightness': round(lux, 2)
        }
        
        mqtt_client.publish(MQTT_TOPIC, json.dumps(data))
        print(f"✓ Gesendet: T={t:.1f}°C H={h:.1f}% P={p:.1f}hPa L={lux:.1f}lux")
        
        # Auf Display anzeigen
        show_values(t, h, p, lux, font=font_big)
        
        sleep(2)
        
except KeyboardInterrupt:
    mqtt_client.loop_stop()
    mqtt_client.disconnect()
    print("\n✓ Programm beendet")
