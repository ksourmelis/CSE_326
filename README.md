CSE_326 Project

Team: K.F.C

Team members:
	1. Kyriakos Sourmelis 27676
	2. Florentia Chrysostomou 30297
	3. Christina Chrysostomou 30470
	
Assigments:
	1. Kyriakos Sourmelis -> Admin module, list.php, dashboard.php
	2. Florentia Chrysostomou -> Submit module, login.php, logout.php, register.php
	3. Christina Chrysostomou -> Search module, schema.sql, seed.sql, db.php


Setup:
	Environment: WSL2 Ubuntu 22.04 LTS

	Install PHP and MySQL (Ubuntu):
		1. Update package lists:
			sudo apt update

		2. Install PHP, MySQL extension, and MySQL server:
			sudo apt install -y php php-mysql mysql-server

		3. Verify installation:
			php -v
			mysql --version

	1. Start MySQL service (if not running):
		sudo service mysql start

	2. Create the database and tables:
		sudo mysql < database/schema.sql

	3. Create the database user and grant privileges (run as MySQL root):
		sudo mysql -e "CREATE USER IF NOT EXISTS 'test_user'@'localhost' IDENTIFIED BY '1234'; GRANT ALL PRIVILEGES ON pothen_esxes_db.* TO 'test_user'@'localhost'; FLUSH PRIVILEGES;"

	4. Seed the database with sample data:
		mysql -u test_user -p1234 pothen_esxes_db < database/seed.sql

	5. Start the PHP built-in web server from the project root:
		php -S localhost:8000

	6. Open your browser at:
		http://localhost:8000

	Default accounts (from seed.sql):
		admin_user   / admin@admin.com       / password3  (role: admin)
		nikos_...    / nikos@parliament.cy   / password1  (role: politician)
		kleovoulos   / kleovoulos@gmail.com  / password2  (role: citizen)

	