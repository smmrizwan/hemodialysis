# Dialysis Patient Management System

## Overview

This is a comprehensive web-based Dialysis Patient Management System built with PHP and MySQL. The system provides a complete solution for managing hemodialysis patients, including patient registration, medical history tracking, laboratory data management, and comprehensive reporting capabilities.

## System Architecture

### Frontend Architecture
- **Technology Stack**: HTML5, CSS3, JavaScript, Bootstrap 5.3.0
- **UI Framework**: Bootstrap with custom CSS styling
- **Icons**: Font Awesome 6.0.0
- **Charts**: Chart.js for data visualization
- **Responsive Design**: Mobile-first approach optimized for tablets and desktop

### Backend Architecture
- **Server Technology**: PHP 8.x
- **Database**: MySQL with PDO for database abstraction
- **Architecture Pattern**: MVC-inspired structure with separation of concerns
- **API Layer**: RESTful endpoints for AJAX operations
- **Session Management**: PHP sessions for user state management

### Database Design
- **ORM**: Custom database abstraction layer using PDO
- **Schema**: Normalized relational database with proper foreign key relationships
- **Tables**: 
  - `patients` - Main patient demographics and medical info
  - `laboratory_data` - Lab test results and trends
  - `catheter_infections` - Infection tracking
  - `dialysis_complications` - Complication monitoring
  - `medical_background` - Patient medical history
  - `hd_prescription` - Hemodialysis prescriptions
  - `vaccinations` - Vaccination records
  - `medications` - Current medications

## Key Components

### Patient Management Module
- **Patient Registration**: Complete demographic and medical information capture
- **Patient Search**: Advanced search functionality by name or file number
- **Patient Editing**: Full CRUD operations for patient records
- **Data Validation**: Client-side and server-side validation

### Laboratory Data Management
- **Monthly Lab Tracking**: Comprehensive lab values including Hb, Iron studies, Calcium, Phosphorus
- **Trend Analysis**: Automatic calculation of percentage changes
- **Data Visualization**: Charts showing lab value trends over time
- **Export Capabilities**: PDF export functionality for lab reports

### Medical History Tracking
- **Medical Background**: Previous dialysis history, transplant history, comorbidities
- **Complications Monitoring**: Dialysis-related complications tracking
- **Catheter Infections**: Infection episodes with organism identification
- **Vaccination Records**: Immunization status and dates

### Prescription Management
- **HD Prescription**: Complete hemodialysis prescription parameters
- **Medications**: Current medication list with dosages and frequencies
- **Access Type**: Vascular access monitoring (AV fistula, catheter)

### Reporting System
- **Patient Reports**: Comprehensive patient summaries
- **PDF Export**: Professional report generation
- **Dashboard Analytics**: Key performance indicators and statistics

## Data Flow

### Patient Registration Flow
1. User accesses patient form
2. Client-side validation occurs on form submission
3. Data is sanitized and validated on server
4. Calculations performed (age, BMI, MAP)
5. Data stored in database with proper relationships
6. Confirmation sent to user

### Laboratory Data Flow
1. Patient selection from dropdown
2. Lab values entered in spreadsheet-style interface
3. Automatic calculations (TSAT, corrected calcium, etc.)
4. Trend analysis performed against previous values
5. Data stored with timestamp and patient association
6. Visual charts updated in real-time

### API Data Flow
1. AJAX requests sent to `/api/` endpoints
2. Server validates request method and parameters
3. Database operations performed with proper error handling
4. JSON responses returned to client
5. Client updates UI based on response

## External Dependencies

### Frontend Dependencies
- **Bootstrap 5.3.0**: UI framework and responsive grid system
- **Font Awesome 6.0.0**: Icon library for consistent iconography
- **Chart.js**: Data visualization library for lab trends
- **jQuery**: DOM manipulation and AJAX operations

### Backend Dependencies
- **PHP 8.x**: Server-side scripting language
- **MySQL**: Primary database engine
- **PDO**: Database abstraction layer
- **TCPDF**: PDF generation library (referenced but not implemented)


### Production Considerations
- **Web Server**: Apache or Nginx recommended for production
- **Database**: Separate MySQL server with proper configuration
- **SSL**: HTTPS implementation for patient data security
- **Backup**: Regular database backups and disaster recovery plan
- **Performance**: Database indexing and query optimization

### Security Measures
- **Input Sanitization**: All user inputs sanitized using custom functions
- **SQL Injection Prevention**: Prepared statements used throughout
- **Data Validation**: Client-side and server-side validation
- **Access Control**: Session-based access management

## User Preferences

Preferred communication style: Simple, everyday language.

## Recent Changes

## Changelog

Changelog:
- June 16, 2025. Initial setup
- June 17, 2025. Database and API fixes completed