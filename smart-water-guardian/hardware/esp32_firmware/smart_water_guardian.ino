#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <ESP32Ping.h>

// WiFi Configuration
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// MQTT Configuration
const char* mqtt_server = "YOUR_AZURE_IOT_HUB_URL";
const int mqtt_port = 8883;
const char* mqtt_user = "YOUR_IOT_HUB_DEVICE_ID";
const char* mqtt_password = "YOUR_DEVICE_SAS_TOKEN";

// Pin Configuration
#define FLOW_SENSOR_PIN 34  // GPIO34 for flow sensor
#define BATT_READ_PIN 35    // GPIO35 for battery reading

// Flow Sensor Variables
volatile int flow_frequency = 0;
float flow_rate = 0.0;
float total_volume = 0.0;
unsigned long last_time = 0;
unsigned long current_time = 0;

// WiFi and MQTT clients
WiFiClientSecure espClient;
PubSubClient client(espClient);

// Function to read flow sensor
void IRAM_ATTR flowISR() {
    flow_frequency++;
}

void setup_wifi() {
    delay(10);
    Serial.println();
    Serial.print("Connecting to ");
    Serial.println(ssid);

    WiFi.begin(ssid, password);

    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }

    Serial.println("");
    Serial.println("WiFi connected");
    Serial.println("IP address: ");
    Serial.println(WiFi.localIP());
}

void reconnect() {
    // Loop until we're reconnected
    while (!client.connected()) {
        Serial.print("Attempting MQTT connection...");
        // Create a random client ID
        String clientId = "ESP32Client-";
        clientId += String(random(0xffff), HEX);
        
        // Attempt to connect
        if (client.connect(clientId.c_str(), mqtt_user, mqtt_password)) {
            Serial.println("connected");
            client.publish("device/status", "online");
            client.subscribe("device/command");
        } else {
            Serial.print("failed, rc=");
            Serial.print(client.state());
            Serial.println(" try again in 5 seconds");
            delay(5000);
        }
    }
}

void setup() {
    Serial.begin(115200);
    
    // Setup flow sensor
    pinMode(FLOW_SENSOR_PIN, INPUT_PULLUP);
    attachInterrupt(digitalPinToInterrupt(FLOW_SENSOR_PIN), flowISR, RISING);
    
    // Setup WiFi and MQTT
    setup_wifi();
    
    espClient.setInsecure(); // For testing only
    client.setServer(mqtt_server, mqtt_port);
    client.setCallback(callback);
}

void callback(char* topic, byte* payload, unsigned int length) {
    Serial.print("Message arrived [");
    Serial.print(topic);
    Serial.print("] ");
    for (int i = 0; i < length; i++) {
        Serial.print((char)payload[i]);
    }
    Serial.println();
    
    // Handle commands
    if (strcmp(topic, "device/command") == 0) {
        String command = String((char*)payload).substring(0, length);
        if (command == "reset") {
            ESP.restart();
        }
    }
}

void sendData() {
    if (!client.connected()) {
        reconnect();
    }
    
    // Calculate flow rate
    current_time = millis();
    if (current_time >= (last_time + 1000)) {
        // Flow rate in L/min = (pulse frequency / 7.5)
        flow_rate = flow_frequency / 7.5;
        flow_frequency = 0;
        last_time = current_time;
        
        // Update total volume
        total_volume += flow_rate / 60; // Convert to L/s
    }
    
    // Read battery voltage
    int batt_raw = analogRead(BATT_READ_PIN);
    float battery_percentage = (batt_raw / 4095.0) * 100.0;
    
    // Create JSON payload
    StaticJsonDocument<256> doc;
    doc["meter_id"] = "METER_001";
    doc["api_key"] = "SMART_WATER_API_KEY_2026";
    doc["flow_rate"] = flow_rate;
    doc["volume"] = total_volume;
    doc["battery"] = battery_percentage;
    doc["timestamp"] = current_time;
    
    // Serialize to JSON
    char jsonBuffer[256];
    serializeJson(doc, jsonBuffer);
    
    // Publish to MQTT
    client.publish("device/data", jsonBuffer);
    
    Serial.print("Sent data: ");
    Serial.println(jsonBuffer);
}

void loop() {
    if (!client.connected()) {
        reconnect();
    }
    client.loop();
    
    // Send data every 60 seconds
    static unsigned long lastSend = 0;
    if (millis() - lastSend >= 60000) {
        sendData();
        lastSend = millis();
    }
    
    delay(10);
}
