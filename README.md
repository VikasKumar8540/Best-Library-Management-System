Best Library Management System

 Author:-
Made by Your Name: Vikas kumar

This is a web based Library Management System made using PHP, MySQL and simple HTML, CSS, JS.
It has three main users — Admin, Librarian and Student. Each one have their own dashboard and features.

Live Preview

You can run this project on local server like XAMPP.
Default URL is:
http://localhost:88/library_management/

Features
Login System
Different login for Admin, Librarian and Student
Password is stored securely
Session is used to control access
 Admin Panel

Admin can do many things like:

See dashboard (books, students, etc.)
Add or remove librarians
Manage students
Add, edit or delete books
Manage categories
Set fine amount
View reports
Update profile
Librarian Panel

Librarian work includes:

Check dashboard stats
Add students
Manage books
Issue books
Return books and calculate fine
See all issued books
Handle book requests
Send notifications
Update profile
 Student Panel

Student can:

See dashboard
Search books
Check issued books
See notifications
Request books
Check fines
Update profile
 Project Structure

Project folders are like this:

admin/ → admin pages  
librarian/ → librarian pages  
student/ → student pages  
includes/ → config and common files  
assets/ → css, js, images  
database.sql → database file  
index.php → login page  
logout.php → logout  
 Technologies Used
Backend → PHP
Database → MySQL
Frontend → HTML, CSS, JavaScript
Icons → Font Awesome
Server → Apache (XAMPP best)
 How to Install
Clone project
Move into XAMPP folder
Create database library_management
Import database.sql
Update config file
Run in browser
 Database Setup

You can also run SQL manually if needed.

ALTER TABLE users ADD COLUMN avatar VARCHAR(255);
ALTER TABLE users ADD UNIQUE (email);
ALTER TABLE users ADD UNIQUE (student_id);
 Default Login

Admin → admin@library.com
 / password
Librarian → librarian@library.com
 / password
Student → student@library.com
 / password

 Change password after login.

 Security
Input is cleaned before use
Password is hashed
Role check is applied
Image upload is validated
 Limitations
No pagination
No email system
No API support
 Contribution

You can contribute if you want:

Fork project
Create branch
Make changes
Push code
Create pull request

