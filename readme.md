# Complaint Management System

A simple web-based Complaint Management System built using PHP and MySQL. The system allows users to register, submit complaints, and track their complaint status, while administrators can view and respond to complaints through an admin dashboard.

---

## Features

### User
- User Registration
- User Login and Logout
- File a Complaint
- View Submitted Complaints
- Track Complaint Status

### Admin
- Admin Login
- View All Complaints
- Reply to User Complaints
- Manage Complaint Status

---

## Project Structure

complaint-management-system/

├── index.php  
├── login.php  
├── register.php  
├── logout.php  

├── admin/  
│   ├── admin_login.php  
│   ├── dashboard.php  
│   ├── view_complaints.php  
│   └── reply_complaint.php  

├── user/  
│   ├── dashboard.php  
│   ├── file_complaint.php  
│   └── my_complaints.php  

├── includes/  
│   ├── header.php  
│   └── footer.php  

├── config/  
│   └── db.php  

└── database/  
    └── schema.sql  

---

## Technologies Used

- PHP  
- MySQL  
- HTML  
- CSS  
- Git  
- GitHub  

---

## Setup Instructions

### 1. Clone the repository

git clone https://github.com/Jenish045/complaint-management-system.git

---

### 2. Move the project to XAMPP

Copy the project folder to:

xampp/htdocs/

Example:

xampp/htdocs/complaint-management-system

---

### 3. Start Apache and MySQL

Open XAMPP Control Panel and start:

Apache  
MySQL

---

### 4. Create the database

Open phpMyAdmin:

http://localhost/phpmyadmin

Create a database named:

cms

---

### 5. Import SQL file

Import the file:

database/schema.sql

---

### 6. Configure database connection

Open:

config/db.php

Update if needed:

host = localhost  
user = root  
password =  
database = cms  

---

### 7. Run the project

Open browser:

http://localhost/complaint-management-system

---

## User Workflow

1. Register a new account  
2. Login to the system  
3. Submit a complaint  
4. View submitted complaints  
5. Check admin response  

---

## Admin Workflow

1. Login through admin panel  
2. View all complaints  
3. Respond to complaints  
4. Update complaint status  

---

## Future Improvements

- Complaint categories  
- Email notifications  
- File upload support  
- Admin analytics dashboard  
- Role-based authentication  
- REST API integration  

---

## Author

Jenish Upadhyay  
