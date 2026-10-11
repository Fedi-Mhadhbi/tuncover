# 🇹🇳 TunCover

> **Discover Tunisia. Explore its culture. Connect with authentic experiences.**

TunCover is a full-stack web platform designed to promote **Tunisian tourism, cultural heritage, local artisans, and authentic experiences**.

The platform provides a centralized digital space where users can discover destinations and activities, explore Tunisian artisans, book experiences, and complete a **simulated payment workflow**.

---

## 🌍 About the Project

Tunisia offers a rich combination of historical sites, cultural heritage, traditional crafts, local artisans, and unique tourism experiences. TunCover aims to bring these experiences together through a single, accessible digital platform.

The platform connects tourists with local experiences while providing dedicated functionality for different user roles, including tourists, artisans, and administrators.

### 🎯 Project Goals

* Promote Tunisian tourism and cultural heritage
* Make local experiences easier to discover
* Give Tunisian artisans a digital presence
* Simplify activity booking and reservation management
* Provide a complete tourism platform experience from discovery to booking and payment

---

## ✨ Features

### 👤 Authentication & User Management

* User registration and login
* Role-based access control
* Session management
* Dedicated interfaces based on user role

### 🗺️ Explore Tunisia

* Discover Tunisian destinations and experiences
* Browse tourism and cultural activities
* Search and filter activities
* View detailed activity information

### 🎨 Artisan Discovery

* Explore Tunisian artisan profiles
* Discover traditional crafts and local products
* Showcase artisan information and work
* Connect tourism experiences with local craftsmanship

### 📅 Booking & Reservations

* Browse available activities
* View activity details and availability
* Make reservations
* Manage bookings from the tourist dashboard
* View reservation history
* Track booking status

### 💳 Simulated Payment System

TunCover includes a **demonstration payment workflow** to simulate the checkout experience for tourism reservations.

* Enter simulated payment details
* Review reservation and payment information
* Complete a simulated payment
* Receive payment confirmation
* Track payment and booking status

> ⚠️ **Demo Payment Notice:** This is a simulated payment system developed for demonstration and educational purposes. It does **not process real transactions and does not connect to a real payment provider**. No real payment card is required.

### 👤 Tourist Dashboard

A personalized dashboard allows tourists to manage their activity on the platform:

* Profile management
* Booking management
* Reservation history
* Payment status
* Access to booked experiences

### 📱 Progressive Web App

TunCover includes **Progressive Web App (PWA)** capabilities, providing an app-like experience directly from a compatible web browser.

---

## 🛠️ Tech Stack

| Layer                | Technologies                       |
| -------------------- | ---------------------------------- |
| **Frontend**         | HTML5, CSS3, JavaScript            |
| **Backend**          | PHP 8, Object-Oriented Programming |
| **Architecture**     | MVC                                |
| **Database**         | MariaDB / MySQL                    |
| **Web Server**       | Nginx / Apache                     |
| **Operating System** | Linux                              |
| **Version Control**  | Git, GitHub                        |
| **Application**      | Progressive Web App (PWA)          |

---

## 🏗️ Project Architecture

TunCover follows a structured architecture separating authentication, public functionality, tourist features, artisan functionality, administration, shared components, and configuration.

```text
TunCover/
│
├── php/
│   ├── auth/          # Authentication and user access
│   ├── public/        # Public-facing pages
│   ├── tourist/       # Tourist dashboard and features
│   ├── artisan/       # Artisan-related functionality
│   ├── admin/         # Administration features
│   ├── shared/        # Shared components and utilities
│   └── config/        # Application configuration
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
└── manifest.json      # Progressive Web App configuration
```

---

## 🗄️ Database

TunCover uses a relational database to manage the application's core data, including:

* Users and roles
* Tourism activities
* Artisan information
* Reservations
* Booking status
* Payment and transaction status

The application uses **MariaDB/MySQL** as its database management system.

---

## 🚀 Getting Started

### Prerequisites

Make sure you have the following installed:

* PHP 8+
* MariaDB or MySQL
* Nginx or Apache
* Git

### Installation

**1. Clone the repository**

```bash
git clone https://github.com/fedimh666/tuncover.git
cd tuncover
```

**2. Configure the database**

Create a MariaDB/MySQL database and configure the application's database connection using your local configuration.

**3. Import the database**

Import the provided database schema into your MariaDB/MySQL server.

**4. Configure the web server**

Configure Nginx or Apache to serve the TunCover project.

**5. Start the application**

Start your web server and database service, then open TunCover in your browser.

> **Note:** Database credentials and environment-specific configuration should be kept local and should not be committed to the repository.

---

## 🔐 Security

TunCover follows basic security practices for authentication, database access, and application configuration.

* Authentication and role-based access control
* Database access through structured backend logic
* Sensitive credentials excluded from the repository
* Configuration separated from application logic
* No real payment card processing
* No real payment credentials are required by the demo payment system

> **Important:** The payment functionality is strictly a simulated demonstration and should not be used for real financial transactions.

---

## 🎯 Future Improvements

* 🗺️ Interactive maps for destinations and activities
* 🌍 Multilingual support
* ⭐ Reviews and ratings
* 🔔 Booking and activity notifications
* 🤖 AI-powered travel recommendations
* 📱 Dedicated mobile application
* 💳 Integration with a real payment provider
* 🔐 Enhanced security and digital identity
* 📊 Advanced analytics for tourism and artisan activity

---

## 👨‍💻 Author

**Fedi Mhadhbi** |
**Tharwa Toumi**

Business Computing Student | Web Developer

Interested in **web development, cybersecurity, AI, and emerging technologies**.

---

## 📄 License

This project is currently developed for **educational and portfolio purposes**.

---

⭐ **If you find TunCover interesting, consider giving the repository a star!**
