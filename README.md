
# 💧 Smart Water Guardian System

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1.svg)
![ESP32](https://img.shields.io/badge/ESP32-Arduino-00979D.svg)
![Firebase](https://img.shields.io/badge/Firebase-Realtime-FFCA28.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

> **An IoT-based Smart Water Monitoring System for South African households and municipalities**

---

## 📖 Table of Contents
- [Overview](#overview)
- [Problem Statement](#problem-statement)
- [Solution](#solution)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Architecture](#system-architecture)
- [Hardware Requirements](#hardware-requirements)
- [Software Requirements](#software-requirements)
- [Installation Guide](#installation-guide)
- [Configuration](#configuration)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Testing](#testing)
- [Deployment](#deployment)
- [Team Members](#team-members)
- [Acknowledgments](#acknowledgments)
- [License](#license)

---

## 🎯 Overview

**Smart Water Guardian** is a comprehensive water monitoring and management system designed to address South Africa's critical water scarcity challenges. The system combines IoT sensors, cloud analytics, and web-based dashboards to provide real-time water usage monitoring, leak detection, and transparent billing information to both consumers and municipalities.

### Key Statistics Addressed:
- **46-47%** Non-Revenue Water (NRW) losses
- **216-233 L/person/day** consumption (vs 173L global average)
- **98%** of water resources already allocated
- Billions of Rands lost annually by municipalities

---

## ⚠️ Problem Statement

South Africa faces a critical water scarcity challenge with virtually no capacity to increase supply. The fundamental issue is not solely infrastructure failure, but the **lack of real-time data visibility and monitoring** between physical water usage and consumer awareness. Currently, no widely accessible system exists that provides:
- Real-time water usage tracking
- Early leak detection
- Transparent billing estimation
- Municipal-level NRW monitoring

---

## 💡 Solution

**Smart Water Guardian** addresses this gap through an integrated platform combining:

```
┌─────────────────────────────────────────────────────────────┐
│                     SMART WATER GUARDIAN                    
├─────────────────────────────────────────────────────────────┤
│  📡 IoT Sensors    ☁️ Cloud Analytics    📊 Web Dashboard  
│  - ESP32           - Azure IoT Hub      - Consumer Portal  
│  - Flow Sensors    - Firebase RTDB      - Municipal Portal 
│  - MQTT Protocol   - Anomaly Detection  - Admin Panel      
├─────────────────────────────────────────────────────────────┤
│  🔔 Alert System    📈 NRW Reports      💰 Bill Estimation
└─────────────────────────────────────────────────────────────┘
```

---

## ✨ Features

### 👤 Consumer Features
| Feature | Description | Priority |
|---------|-------------|----------|
| **Real-time Monitoring** | Live water usage in L/min with 60-second updates | High |
| **Leak Detection** | Automatic detection and instant alerts | High |
| **Usage History** | Daily, weekly, and monthly usage charts | Medium |
| **Bill Estimator** | Real-time bill calculation based on usage | Medium |
| **Custom Thresholds** | Set personal usage limits and alerts | Medium |
| **Usage Comparison** | Compare with similar households | Low |

### 🏛️ Municipal Features
| Feature | Description | Priority |
|---------|-------------|----------|
| **City Dashboard** | Real-time city-wide water distribution view | High |
| **NRW Reporting** | Automated NRW reports by suburb/region | High |
| **Top Loss Areas** | Identify top 10 areas with highest water loss | High |
| **Consumer Management** | Manage consumer accounts and meters | Medium |
| **Tariff Management** | Configure and manage water tariffs | Medium |
| **Demand Forecasting** | ML-based predictions (Future) | Low |

### ⚙️ Admin Features
- Device registration and management
- System configuration
- User role management
- Audit logging
- System health monitoring

---

## 🛠️ Technology Stack

### Backend Technologies
```
┌─────────────────────────────────────────────────────────────┐
│                    BACKEND TECHNOLOGIES                     │
├─────────────────────────────────────────────────────────────┤
│  PHP 7.4+       │  FastAPI    │  Laravel   │  .NET 8        │
│  REST API       │  MQTT       │  JWT Auth  │  Firebase      │
│  MySQL 5.7+     │  Redis      │  Azure     │  Chart.js      │
└─────────────────────────────────────────────────────────────┘
```

### Frontend Technologies
```
┌─────────────────────────────────────────────────────────────┐
│                   FRONTEND TECHNOLOGIES                     │
├─────────────────────────────────────────────────────────────┤
│  HTML5          │  CSS3       │  JavaScript │  Chart.js     │
│  Bootstrap 5    │  Axios      │  Firebase   │  Responsive   │
└─────────────────────────────────────────────────────────────┘
```

### Hardware Components
```
┌─────────────────────────────────────────────────────────────┐
│                    HARDWARE COMPONENTS                      │
├─────────────────────────────────────────────────────────────┤
│  ESP32 UNO R3   │  YF-S201    │  Breadboard │  Power        │
│  WiFi           │  Bluetooth  │  Sensors    │  Jumper       │
└─────────────────────────────────────────────────────────────┘
```

---

## 🏗️ System Architecture

```
┌────────────────────────────────────────────────────────────────┐
                     SYSTEM ARCHITECTURE                        
├────────────────────────────────────────────────────────────────┤
│                                                                
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         
│  │   ESP32     │───▶│ Azure IoT  │───▶│   MySQL     │         
│  │  (Sensor)   │    │    Hub      │    │  Database   │          
│  └─────────────┘    └─────────────┘    └─────────────┘      
│         │                  │                   │              
│         │                  ▼                   ▼             
│         │           ┌─────────────┐    ┌─────────────┐      
│         │           │  Firebase   │    │   PHP API   │      
│         │           │  Realtime   │◀───│   Backend   │      
│         │           └─────────────┘    └─────────────┘      
│         │                  │                   │              
│         │                  ▼                   ▼              
│         │           ┌─────────────┐    ┌─────────────┐      
│         └──────────▶│   Web/      │    │  Municipal  │      
│                      │  Consumer   │    │  Dashboard  │      
│                      │  Dashboard  │    │             │      
│                      └─────────────┘    └─────────────┘      
│                                                                
└────────────────────────────────────────────────────────────────┘
```

### Data Flow
1. **ESP32** reads flow sensor data every 60 seconds
2. **MQTT Protocol** sends data to Azure IoT Hub
3. **PHP Backend** processes and stores data in MySQL
4. **Firebase Realtime** updates for live dashboard updates
5. **Web Dashboard** displays real-time data to users

---

## 🔧 Hardware Requirements

### Bill of Materials (R816 per unit)

| Item | Description | Quantity | Unit Price | Total |
|------|-------------|----------|------------|-------|
| **ESP32 UNO R3** | Development Board with WiFi/Bluetooth | 1 | R380 | R380 |
| **YF-S201** | Water Flow Sensor (Hall Effect) | 1 | R144 | R144 |
| **Breadboard** | Solderless Breadboard with Power Supply | 1 | R157 | R157 |
| **Humidity Sensor** | Humidity Detection Module | 1 | R135 | R135 |
| **Jumper Wires** | Connection Wires | 1 | Included | Included |
| **TOTAL** | | | | **R816** |

### ESP32 Specifications
```
┌────────────────────────────────────────────────────┐
│              ESP32 TECHNICAL SPECIFICATIONS         
├────────────────────────────────────────────────────┤
│  Microcontroller   : ESP32-D0WDQ6 (Dual-core)     
│  Clock Speed      : Up to 240 MHz                  
│  SRAM             : 520 KB                         
│  Flash Memory     : 4 MB                           
│  WiFi             : 802.11 b/g/n (2.4 GHz)        
│  Bluetooth        : v4.2 BR/EDR and BLE           
│  GPIO Pins        : 34 (multiple functions)       
│  Operating Voltage: 3.3V                          
│  Input Voltage    : 5V via USB or 7-12V via VIN  
└────────────────────────────────────────────────────┘
```

### YF-S201 Flow Sensor Specifications
```
┌────────────────────────────────────────────────────┐
│           YF-S201 FLOW SENSOR SPECIFICATIONS       │
├────────────────────────────────────────────────────┤
│  Model           : YF-S201 (Hall Effect)           │
│  Pipe Size       : 1/2 inch (DN15)                 │
│  Flow Rate Range : 1 - 30 L/min                    │
│  Operating Voltage: 5 - 24V DC                     │
│  Current Consumption: 15mA (max)                   │
│  Output          : Pulse frequency (square wave)   │
│  Pulse per Liter : ~450 pulses                     │
│  Accuracy        : ±10%                            │
│  Operating Temp  : -25°C to +80°C                  │
└────────────────────────────────────────────────────┘
```

---

## 💻 Software Requirements

### Development Environment
```
┌────────────────────────────────────────────────────┐
│              DEVELOPMENT ENVIRONMENT                
├────────────────────────────────────────────────────┤
│  OS              : Windows 10/11, Linux, macOS    
│  Web Server      : Apache 2.4+ (XAMPP)            
│  PHP Version     : 7.4 or higher                   
│  Database        : MySQL 5.7+                      
│  IDE             : VS Code / PHPStorm             
│  Git             : Version Control                 
└────────────────────────────────────────────────────┘
```

### PHP Extensions Required
```php
- PDO_MySQL
- JSON
- Curl
- OpenSSL
- mbstring
- XML
- GD
- Fileinfo
```

### Composer Dependencies
```json
{
    "require": {
        "firebase/php-jwt": "^6.0",
        "vlucas/phpdotenv": "^5.0",
        "phpmailer/phpmailer": "^6.5",
        "guzzlehttp/guzzle": "^7.0",
        "monolog/monolog": "^2.0"
    }
}
```

---

## 📥 Installation Guide

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/smart-water-guardian.git
cd smart-water-guardian
```

### Step 2: Setup XAMPP
```bash
# Download and install XAMPP from https://www.apachefriends.org/
# Start Apache and MySQL services
```

### Step 3: Database Setup
```bash
# 1. Open phpMyAdmin (http://localhost/phpmyadmin)
# 2. Create database: smart_water_db
# 3. Import migration.sql:
mysql -u root -p smart_water_db < backend/database/migration.sql
```

### Step 4: Configure Environment
```bash
# Copy environment configuration
cp .env.example .env

# Update with your credentials
# Edit .env file with your configurations:
```

### Step 5: Install Dependencies
```bash
# Navigate to backend directory
cd backend

# Install Composer dependencies
composer install

# Install NPM dependencies (frontend)
npm install
```

### Step 6: Configure Firebase
```bash
# 1. Go to Firebase Console (https://console.firebase.google.com/)
# 2. Create new project
# 3. Enable Realtime Database
# 4. Get your database URL and secret
# 5. Update firebase.php configuration
```

### Step 7: Configure ESP32
```bash
# 1. Install Arduino IDE
# 2. Install ESP32 board library
# 3. Open smart_water_guardian.ino
# 4. Update WiFi credentials
# 5. Update MQTT/Azure credentials
# 6. Upload to ESP32
```

### Step 8: Run Application
```bash
# Access the application:
http://localhost/smart-water-guardian/backend/public/

# Default Admin Credentials:
Email: admin@smartwater.co.za
Password: Admin@2026
```

---

## ⚙️ Configuration

### Environment Variables (.env)
```env
# Application Settings
APP_NAME="Smart Water Guardian"
APP_ENV="development"
APP_DEBUG=true
APP_URL="http://localhost/smart-water-guardian"

# Database Settings
DB_HOST="localhost"
DB_NAME="smart_water_db"
DB_USERNAME="root"
DB_PASSWORD=""

# JWT Settings
JWT_SECRET="your_jwt_secret_key_here"
JWT_EXPIRY=2592000  # 30 days in seconds

# API Settings
API_KEY="SMART_WATER_API_KEY_2026"
RATE_LIMIT=100

# Firebase Settings
FIREBASE_URL="https://your-project.firebaseio.com/"
FIREBASE_SECRET="your_firebase_secret"

# Email Settings
MAIL_HOST="smtp.gmail.com"
MAIL_PORT=587
MAIL_USERNAME="your_email@gmail.com"
MAIL_PASSWORD="your_app_password"

# Alert Thresholds
LEAK_FLOW_RATE=20      # L/min
CRITICAL_LEAK_RATE=30  # L/min
LOW_BATTERY=20         # %
OFFLINE_TIMEOUT=7200   # seconds
```

### Database Configuration
```php
// backend/api/config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "smart_water_db";
    private $username = "root";
    private $password = "";
    // ...
}
```

### Firebase Configuration
```php
// backend/api/config/firebase.php
class Firebase {
    private $base_url = "https://your-project.firebaseio.com/";
    private $secret = "your_firebase_secret";
    // ...
}
```

---

## 📚 API Documentation

### Authentication Endpoints

#### 1. User Registration
```http
POST /api/modules/auth/register.php
Content-Type: application/json

{
    "name": "John",
    "surname": "Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "phone": "0821234567",
    "address": "123 Main St, Johannesburg",
    "property_id": "PROP_001"
}

Response:
{
    "success": true,
    "token": "jwt_token_here",
    "user": {
        "id": "USR_123",
        "name": "John",
        "email": "john@example.com",
        "role": "consumer"
    }
}
```

#### 2. User Login
```http
POST /api/modules/auth/login.php
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "SecurePass123!"
}

Response:
{
    "success": true,
    "token": "jwt_token_here",
    "user": {
        "id": "USR_123",
        "name": "John",
        "surname": "Doe",
        "email": "john@example.com",
        "role": "consumer"
    }
}
```

### IoT Endpoints

#### 3. Ingest Sensor Data
```http
POST /api/modules/iot/ingest_data.php
Content-Type: application/json
X-API-Key: SMART_WATER_API_KEY_2026

{
    "meter_id": "METER_001",
    "api_key": "SMART_WATER_API_KEY_2026",
    "flow_rate": 12.5,
    "volume": 350.2,
    "battery": 85.0,
    "timestamp": "2026-03-12 10:30:00"
}

Response:
{
    "success": true,
    "message": "Data ingested successfully",
    "anomalies_detected": 1,
    "alerts": [
        {
            "type": "leak",
            "severity": "high",
            "message": "Possible leak detected: 12.5 L/min"
        }
    ]
}
```

### Dashboard Endpoints

#### 4. Consumer Dashboard
```http
GET /api/modules/dashboard/consumer.php
Authorization: Bearer {jwt_token}

Response:
{
    "success": true,
    "data": {
        "properties": [
            {
                "property_id": "PROP_001",
                "address": "123 Main St, Johannesburg",
                "meter_id": "METER_001",
                "current_usage": {
                    "flow_rate": 12.5,
                    "today_total": 350.2,
                    "unit": "L/min"
                },
                "weekly_usage": [...],
                "monthly_usage": [...],
                "active_alerts": [...]
            }
        ]
    }
}
```

#### 5. Municipal Dashboard
```http
GET /api/modules/dashboard/municipal.php
Authorization: Bearer {jwt_token}

Response:
{
    "success": true,
    "data": {
        "summary": {
            "total_meters": 150,
            "online_meters": 142,
            "total_consumers": 120,
            "today_usage": 1250000,
            "nrw_percentage": 35.5,
            "nrw_volume": 443750
        },
        "top_nrw_areas": [...],
        "daily_trend": [...],
        "recent_alerts": [...]
    }
}
```

### Report Endpoints

#### 6. Generate NRW Report
```http
POST /api/modules/reports/generate_nrw.php
Authorization: Bearer {jwt_token}

{
    "start_date": "2026-01-01",
    "end_date": "2026-01-31"
}

Response:
{
    "success": true,
    "report": {
        "period": {
            "start": "2026-01-01",
            "end": "2026-01-31"
        },
        "summary": {
            "total_supplied": 1250000,
            "total_billed": 806250,
            "nrw_volume": 443750,
            "nrw_percentage": 35.5
        },
        "breakdown": [...]
    }
}
```

---

## 🗃️ Database Schema

### ER Diagram
```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    users     │───▶ │  properties  │────▶│    meters   │
│──────────────│     │──────────────│     │──────────────│
│ id (PK)      │     │ id (PK)      │     │ id (PK)      │
│ name         │     │ user_id (FK) │     │ meter_id (U) │
│ surname      │     │ address      │     │ property_id  │
│ email (U)    │     │ property_type│     │ status       │
│ password     │     │ latitude     │     │ battery_level│
│ role         │     │ longitude    │     │ last_reading │
│ phone        │     └──────────────┘     │ install_date │
│ address      │                          └──────────────┘
└──────────────┘                                 │
       │                                         │
       ▼                                         ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    alerts    │     │   readings   │     │ water_supply │
│──────────────│     │──────────────│     │──────────────│
│ id (PK)     │     │ id (PK)      │     │ id (PK)      │
│ user_id (FK)│     │ meter_id (FK)│     │ suburb       │
│ meter_id    │     │ flow_rate    │     │ supply_date  │
│ alert_type  │     │ volume       │     │ volume_suppl.│
│ message     │     │ battery_level│     │ source       │
│ severity    │     │ reading_time │     └──────────────┘
│ status      │     └──────────────┘
│ created_at  │
│ resolved_at │
└──────────────┘
```

### Key Tables Description

#### Users Table
```sql
CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    role ENUM('consumer', 'municipal', 'admin', 'technician'),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Readings Table
```sql
CREATE TABLE readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meter_id VARCHAR(50),
    flow_rate DECIMAL(10, 2),
    volume DECIMAL(10, 2),
    battery_level DECIMAL(5, 2),
    reading_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meter_id) REFERENCES meters(id) ON DELETE CASCADE,
    INDEX idx_meter_reading (meter_id, reading_time)
);
```

---

## 🧪 Testing

### API Testing with Postman
```bash
# Import Postman Collection
# File: docs/Smart_Water_Guardian.postman_collection.json

# Run Tests
npm test
```

### Manual Testing Checklist
- [ ] User Registration
- [ ] User Login
- [ ] JWT Token Validation
- [ ] Consumer Dashboard Load
- [ ] Municipal Dashboard Load
- [ ] Sensor Data Ingestion
- [ ] Alert Generation
- [ ] NRW Report Generation
- [ ] Database Queries
- [ ] Firebase Real-time Updates
- [ ] Responsive Design
- [ ] Cross-browser Compatibility

### Performance Testing
```bash
# Load testing with Apache Bench
ab -n 1000 -c 100 http://localhost/api/modules/dashboard/consumer.php

# Expected Results:
# - Response Time: < 500ms
# - Throughput: > 100 req/sec
# - Error Rate: < 1%
```

---

## 🚀 Deployment

### Production Deployment Checklist

#### 1. Server Requirements
```bash
# Recommended Server Specs
CPU: 2+ Cores
RAM: 4GB+
Storage: 50GB SSD
OS: Ubuntu 20.04 LTS
```

#### 2. Security Setup
```bash
# Enable HTTPS (SSL/TLS)
# Setup firewall
# Configure rate limiting
# Enable WAF
# Setup DDoS protection
```

#### 3. Database Optimization
```bash
# Create indexes
# Enable query caching
# Setup daily backups
# Configure replication (if needed)
```

#### 4. Monitoring
```bash
# Setup application monitoring (New Relic)
# Setup server monitoring (Prometheus)
# Configure alert notifications
# Setup log aggregation
```

#### 5. Docker Deployment (Optional)
```yaml
# docker-compose.yml
version: '3.8'

services:
  web:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./backend:/var/www/html
      - ./nginx.conf:/etc/nginx/conf.d/default.conf

  php:
    image: php:8.0-fpm
    volumes:
      - ./backend:/var/www/html

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: smart_water_db
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:alpine

volumes:
  mysql_data:
```

---

## 👥 Team Members

| Name | Student Number | Role | Responsibilities |
|------|---------------|------|------------------|
| **Sandile Sibeko** | 220115085 | Project Lead / Backend | Python/C# development, architecture |
| **Mongiwethu Eddy Ncube** | 221152725 | IoT Hardware Lead | ESP32/C++/PHP programming, sensor integration |
| **Keamogetse Selebano** | 220068905 | Frontend Lead | React/HTML/CSS, web development |
| **Ndzulamo Michelle Yingwani** | 220122253 | Database Specialist | SQL Azure, Firebase, data modeling |
| **Hlonipho Nersely Bila** | 220080694 | UI/UX Designer | Wireframes, user testing |
| **Zizile Ezona Mbanqi** | 220061777 | QA & Testing | Test cases, quality assurance |
| **Bongani Sithole** | 219027546 | API / Backend Developer | REST API development, backend integration |

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2026 Code Crew Innovators

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🙏 Acknowledgments

- **Department of Water and Sanitation (DWS)** - For providing valuable data and insights
- **Azure for Education** - For cloud credits and resources
- **XAMPP Community** - For development environment
- **Arduino Community** - For hardware documentation
- **Firebase Team** - For real-time database

---

## 📞 Contact

**Project Lead:** Sandile Sibeko
- Email: sandile.sibeko@example.com
- GitHub: @sandilesibeko

**Project Repository:** https://github.com/yourusername/smart-water-guardian

**Live Demo:** https://smart-water-guardian.example.com

---

## 📝 Changelog

### Version 1.0.0 (March 2026)
- ✅ Initial release
- ✅ Complete consumer dashboard
- ✅ Municipal dashboard implementation
- ✅ IoT sensor integration
- ✅ Real-time data updates via Firebase
- ✅ NRW reporting system
- ✅ Alert and notification system
- ✅ User authentication and authorization
- ✅ Complete documentation

### Version 1.1.0 (Planned)
- 📱 Mobile application
- 📊 Advanced analytics
- 🤖 AI-based demand forecasting
- 💳 Payment gateway integration
- 📡 Additional sensor support

---

## 🏆 Success Criteria

The project is considered successful if:

1. ✅ System achieves **99.5% uptime** during pilot phase
2. ✅ At least **80% of users** receive alerts within 5 minutes of leak detection
3. ✅ Municipal NRW reporting reduces investigation time by **50%**
4. ✅ User satisfaction rating exceeds **4/5** in pilot surveys
5. ✅ System accurately detects leaks with **> 90%** accuracy
6. ✅ Bill estimation accuracy within **±5%**
7. ✅ Data latency < **60 seconds**

---

**Built with ❤️ by Code Crew Innovators**

---

> *"Smart Water Guardian - Promoting responsible water usage in South Africa through real-time monitoring, early leak detection, and transparent water information."*

```

---

## 📁 Additional Files to Include

### .env.example
```env
# Application
APP_NAME="Smart Water Guardian"
APP_ENV=development
APP_DEBUG=true
APP_URL="http://localhost/smart-water-guardian"

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=smart_water_db
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=change_this_to_a_strong_secret_key
JWT_EXPIRY=2592000

# API
API_KEY=SMART_WATER_API_KEY_2026
RATE_LIMIT=100

# Firebase
FIREBASE_URL=https://your-project.firebaseio.com/
FIREBASE_SECRET=your_firebase_secret

# Email
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=no-reply@smartwater.co.za

# Alert Thresholds
LEAK_FLOW_RATE=20
CRITICAL_LEAK_RATE=30
LOW_BATTERY=20
OFFLINE_TIMEOUT=7200

# AWS (Optional)
AWS_ACCESS_KEY=
AWS_SECRET_KEY=
AWS_REGION=af-south-1
AWS_BUCKET=smart-water-guardian
```

### .gitignore
```gitignore
# Environment
.env
.env.local
.env.*.local

# Logs
*.log
logs/
error_log

# IDE
.vscode/
.idea/
*.sublime-*

# OS
.DS_Store
Thumbs.db

# Dependencies
node_modules/
vendor/
composer.lock
package-lock.json

# Build
dist/
build/
*.min.js
*.min.css

# Uploads
uploads/
*.zip
*.tar.gz

# Cache
cache/
temp/
*.tmp
```

### composer.json
```json
{
    "name": "smart-water-guardian/backend",
    "description": "Smart Water Guardian Backend API",
    "type": "project",
    "require": {
        "php": "^7.4|^8.0",
        "firebase/php-jwt": "^6.0",
        "vlucas/phpdotenv": "^5.0",
        "phpmailer/phpmailer": "^6.5",
        "guzzlehttp/guzzle": "^7.0",
        "monolog/monolog": "^2.0",
        "predis/predis": "^1.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "squizlabs/php_codesniffer": "^3.0",
        "mockery/mockery": "^1.4"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ]
    }
}
```

### package.json (Frontend)
```json
{
    "name": "smart-water-guardian-frontend",
    "version": "1.0.0",
    "description": "Smart Water Guardian Web Interface",
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "preview": "vite preview",
        "test": "jest"
    },
    "dependencies": {
        "chart.js": "^4.0.0",
        "axios": "^1.0.0",
        "firebase": "^9.0.0",
        "bootstrap": "^5.3.0",
        "sweetalert2": "^11.0.0"
    },
    "devDependencies": {
        "vite": "^4.0.0",
        "sass": "^1.0.0",
        "jest": "^29.0.0"
    }
}
```

---

This complete README file provides comprehensive documentation for your Smart Water Guardian project, covering everything from installation to deployment. Good luck with your final year project! 🚀
