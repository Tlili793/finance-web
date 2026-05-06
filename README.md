# 🏦 FinanceApp – Intelligent Financial & Insurance Platform

![Symfony](https://img.shields.io/badge/Symfony-6.4-black?style=for-the-badge&logo=symfony)
![FastAPI](https://img.shields.io/badge/FastAPI-0.109-009688?style=for-the-badge&logo=fastapi)
![Python](https://img.shields.io/badge/Python-3.10-3776AB?style=for-the-badge&logo=python)
![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker)

FinanceApp is a modern, enterprise-grade financial management platform that combines traditional fintech workflows (Loans, Insurance, Budgets) with cutting-edge AI capabilities. It features a distributed architecture with a Symfony core and a Python-based AI microservice.

---

## 🏗️ Architecture Overview

The project is divided into two main services:

1.  **Core Backend (PHP/Symfony)**: Handles business logic, database management, security (JWT/OAuth), and the administrative dashboard.
2.  **AI Microservice (Python/FastAPI)**: Manages Large Language Model (LLM) integrations via Groq Cloud, providing an intelligent insurance assistant and natural language database searching.

---

## 🛠️ Tech Stack

### Backend (PHP)
- **Framework**: Symfony 6.4 (LTS)
- **Database**: MySQL 8.0 / Doctrine ORM
- **Frontend**: Symfony UX (Stimulus, Turbo), Twig, Webpack Encore
- **API**: API Platform (REST/GraphQL)
- **Security**: LexikJWTAuthentication, Symfony Security
- **Document Management**: Dompdf (PDF Generation), BoldSign (E-Signatures)

### AI Microservice (Python)
- **Framework**: FastAPI
- **Model**: Llama 3.3 70B (via Groq API)
- **Integration**: `httpx` for asynchronous LLM calls

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx
- **Real-time**: Symfony Mercure Hub

---

## 🚀 Getting Started

### Prerequisites
- Docker & Docker Compose
- Groq API Key (for AI features)

### Installation

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/mohamedwassimtlili/finance-web.git
    cd finance-web
    ```

2.  **Configure Environment**:
    Create a `.env` file in the root:
    ```env
    # Symfony
    DATABASE_URL="mysql://user:password@db:3306/finance_app?serverVersion=8.0"
    
    # AI Service (FastAPI)
    GROQ_API_KEY=your_groq_key_here
    ```

3.  **Start with Docker**:
    ```bash
    docker-compose up -d --build
    ```

4.  **Initialize Database**:
    ```bash
    docker-compose exec php php bin/console doctrine:migrations:migrate
    ```

---

## 💎 Core Features

### 1. 🛡️ Insurance Module
- **Package Management**: Create and manage customizable insurance plans.
- **Contract Requests**: Automated workflow for user applications.
- **Electronic Signatures**: Seamless integration with **BoldSign** for legally binding contracts.
- **AI Assistant**: A stateful chatbot that helps users understand policy details and coverage.

### 2. 💸 Financial Management
- **Loans & Repayments**: Complete tracking of loan issuance, interest rates, and payment schedules.
- **Budgeting**: User-defined budgets with real-time expense tracking.
- **PDF Generation**: Automated generation of receipts and loan certificates.

### 3. 🤖 AI Capabilities
- **Natural Language User Search**: Admins can search users using queries like *"Show me unverified Google users created last week"*.
- **Intelligent Assistant**: Context-aware assistance for insurance-related queries.

### 4. 👨‍💼 Administration Dashboard
- **Bento Box UI**: A modern, responsive dashboard with real-time stats.
- **User Management**: Role-based access control (RBAC), activity tracking, and status toggling.

---

## 🧪 Testing Strategy

We follow a strict **Business Manager Layer** pattern to ensure core logic is framework-agnostic and 100% testable.

- **Unit Tests**: Run `php bin/phpunit tests/Service/` for business logic validation.
- **Integration Tests**: Run `php bin/phpunit tests/Controller/` for API and UI testing.
- **Static Analysis**: Enforced by **PHPStan** (Level 9) to ensure type-safety.

For more details, see [testing_documentation.md](./testing_documentation.md).

---

## 📁 Project Structure

```text
├── api/                # Python FastAPI Microservice
│   ├── main.py         # AI API Entry point
│   ├── services/       # AI Logic & LLM Prompts
│   └── Dockerfile
├── config/             # Symfony Configuration
├── docker/             # Docker Configuration (Nginx, PHP)
├── src/
│   ├── Controller/     # Admin & API Controllers
│   ├── Entity/         # Doctrine Entities (Domain Model)
│   ├── Repository/     # Database Queries (inc. AI search filters)
│   └── Service/        # Core Business Logic (Managers)
├── templates/          # Twig Templates (Modern UI)
└── docker-compose.yaml # Orchestration
```

---

## 👥 Team & Contributors
Developed by:
- **Mohamed Wassim Tlili**
- **Fathi Mejri**
- **Hanin Limam**
- **Salma Boubakri**
- **Yasmine Ben Abedelkader**

## 📄 License
Proprietary.
