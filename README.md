# 🌍 Travel Guide Management System

Travel Guide is a simple web application for people who love travel.

Users can find travel places, read travel information, save places, and write comments.

The system also has **Scout** and **Admin** roles to manage travel posts and users.

## ✨ Features

### 👤 User

* Create an account
* Login and logout
* View travel places
* Read travel information
* Add places to Wishlist
* Remove places from Wishlist
* Write comments
* Manage profile

### 🧭 Scout

* Login as a Scout
* Create travel post requests
* Edit post requests
* See request status
* See approved posts
* Browse travel places

### 🛠️ Admin

* View dashboard
* Manage users
* Verify users
* Remove users
* Approve or reject travel posts
* Edit travel posts
* Manage comments
* Browse all travel places

## 💻 Technologies

This project uses:

* PHP
* MySQL
* HTML
* CSS
* JavaScript
* XAMPP

## 📁 Project Structure

```text
Travel/
│
├── config.php
├── controllers.php
├── models.php
├── database.sql
├── index.php
├── style.css
│
├── public/
│   └── uploads/
│       ├── posts/
│       └── profile/
│
└── views/
    ├── admin/
    ├── auth/
    ├── layouts/
    ├── scout/
    └── user/
```

## ⚙️ How to Run

### 1. Install XAMPP

Install **XAMPP** on your computer.

Start:

* Apache
* MySQL

### 2. Copy the Project

Copy the `Travel` folder to:

```text
C:\xampp\htdocs\
```

### 3. Create the Database

Open:

```text
http://localhost/phpmyadmin
```

Import the file:

```text
database.sql
```

The database name is:

```text
travel_guide_db
```

### 4. Check Database Settings

Open:

```text
config.php
```

The default settings are:

```php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'travel_guide_db';
```

Change them if your MySQL settings are different.

### 5. Open the Website

Open this in your browser:

```text
http://localhost/Travel/
```

## 🔐 Default Admin Account

The system creates a default admin account automatically.

```text
Email: admin@travelguide.com
Password: admin123
```

**Important:** Change the default password before using the project in a real system.

## 👥 User Roles

| Role  | Main Work                              |
| ----- | -------------------------------------- |
| User  | Browse places, wishlist, comments      |
| Scout | Create and manage travel post requests |
| Admin | Manage users, posts, and comments      |

## 🗄️ Database

The project uses MySQL.

Main tables:

* `users`
* `posts`
* `post_requests`
* `wishlist`
* `comments`
* `cost_estimates`

## 🔒 Security

The project includes some basic security features:

* Password hashing
* Login sessions
* CSRF protection
* Input escaping
* Image type checking
* Image size checking
* Role-based access

## 🚀 Future Improvements

Some future features can be:

* Travel map
* Search by location
* Better mobile design
* Travel reviews and ratings
* Online booking
* Email notifications
* Better cost estimation

## 📄 License

This project is made for learning and educational use.

---

## 👨‍💻 Author

**Arghya**

If you like this project, you can ⭐ the repository.
