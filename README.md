# HRMS - Human Resource Management System

A comprehensive Human Resource Management System built with Vue.js frontend, PHP API backend, and MySQL database.

## 🚀 Features

### Employee Management
- Employee registration and profile management
- Employee directory with search and filters
- Employee hierarchy and organizational chart
- Document management (contracts, certificates, etc.)

### Attendance & Time Tracking
- Clock in/out system
- Attendance reports and analytics
- Leave management (vacation, sick leave, etc.)
- Overtime tracking

### Payroll Management
- Salary calculation and processing
- Payslip generation
- Tax calculations
- Bonus and deduction management

### Performance Management
- Performance reviews and evaluations
- Goal setting and tracking
- 360-degree feedback
- Performance analytics

### Recruitment
- Job posting management
- Applicant tracking system
- Interview scheduling
- Candidate evaluation

### Reports & Analytics
- Employee reports
- Attendance analytics
- Payroll reports
- Performance dashboards

## 🏗️ Architecture

```
hrms/
├── frontend/          # Vue.js application
│   ├── src/
│   ├── public/
│   ├── package.json
│   └── ...
├── backend/           # PHP API
│   ├── api/
│   ├── config/
│   ├── models/
│   ├── controllers/
│   └── ...
├── database/          # MySQL database
│   ├── migrations/
│   ├── seeds/
│   └── schema.sql
└── docs/             # Documentation
```

## 🛠️ Technology Stack

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Vue Router** - Client-side routing
- **Pinia** - State management
- **Tailwind CSS** - Utility-first CSS framework
- **Axios** - HTTP client
- **Chart.js** - Data visualization
- **Element Plus** - UI component library

### Backend
- **PHP 8+** - Server-side scripting
- **MySQL 8+** - Database
- **JWT** - Authentication
- **PHPMailer** - Email functionality
- **TCPDF** - PDF generation

### Development Tools
- **Vite** - Build tool for frontend
- **Composer** - PHP dependency manager
- **npm/yarn** - Node package manager

## 📋 Prerequisites

- Node.js (v16 or higher)
- PHP (v8.0 or higher)
- MySQL (v8.0 or higher)
- Composer
- Web server (Apache/Nginx) or XAMPP/WAMP

## 🚀 Quick Start

### 1. Clone the repository
```bash
git clone <repository-url>
cd hrms
```

### 2. Setup Backend
```bash
cd backend
composer install
cp config/config.example.php config/config.php
# Update database credentials in config.php
```

### 3. Setup Database
```bash
mysql -u root -p
CREATE DATABASE hrms_db;
USE hrms_db;
source database/schema.sql;
```

### 4. Setup Frontend
```bash
cd frontend
npm install
npm run dev
```

### 5. Access the application
- Frontend: http://localhost:5173
- Backend API: http://localhost/hrms/backend/api

## 📁 Project Structure

### Frontend Structure
```
frontend/
├── src/
│   ├── components/     # Reusable components
│   ├── views/         # Page components
│   ├── router/        # Route definitions
│   ├── stores/        # Pinia stores
│   ├── services/      # API services
│   ├── utils/         # Utility functions
│   ├── assets/        # Static assets
│   └── styles/        # Global styles
├── public/            # Public assets
└── package.json       # Dependencies
```

### Backend Structure
```
backend/
├── api/
│   ├── auth/          # Authentication endpoints
│   ├── employees/     # Employee management
│   ├── attendance/    # Attendance tracking
│   ├── payroll/       # Payroll management
│   └── reports/       # Reports and analytics
├── config/            # Configuration files
├── models/            # Data models
├── controllers/       # Business logic
├── middleware/        # Request middleware
└── utils/             # Utility functions
```

## 🔐 Security Features

- JWT-based authentication
- Role-based access control (RBAC)
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- Password hashing (bcrypt)

## 📱 Responsive Design

- Mobile-first approach
- Responsive layouts for all screen sizes
- Touch-friendly interface
- Progressive Web App (PWA) capabilities

## 🧪 Testing

- Unit tests for components
- API endpoint testing
- Integration tests
- E2E testing with Cypress

## 📈 Performance

- Lazy loading of routes
- Component code splitting
- Image optimization
- Database query optimization
- Caching strategies

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 📞 Support

For support and questions, please contact [your-email@example.com]

---

**Happy coding! 🎉**