-- Insert demo users (politicians)
INSERT INTO users (username, email, password_hash, role, created_at) VALUES
(
  'nikos_christodoulides',
  'nikos@parliament.cy',
  '$password1',
  'politician',
  NOW()
),
(
  'anna_mavrides',
  'anna@parliament.cy',
  '$password2',
  'politician',
  NOW()
),
(
  'george_papadopoulos',
  'george@parliament.cy',
  '$password3',
  'politician',
  NOW()
),
(
  'admin_user',
  'admin@parliament.cy',
  '$password4',
  'admin',
  NOW()
);

INSERT INTO declarations (user_id, declaration_year, party, position, properties, vehicles, income, created_at) VALUES

(
  1, 
  2023, 
  'DISY (Democratic Rally)', 
  'President of Parliament',
  'House in Nicosia (Engomi), Apartment in Larnaca seafront',
  'Mercedes-Benz S-Class 2022, Toyota Yaris 2018',
  185000.00,
  NOW()
),
(
  1, 
  2024, 
  'DISY (Democratic Rally)', 
  'President of Parliament',
  'House in Nicosia (Engomi), Apartment in Larnaca seafront, Commercial space in Limassol',
  'Mercedes-Benz S-Class 2022, Toyota Yaris 2018, BMW 3 Series 2023',
  195000.00,
  NOW()
),
(
  2, 
  2023, 
  'AKEL (Progressive Party of Working People)', 
  'Member of Parliament',
  'Apartment in Larnaca, Land plot in Troodos',
  'Volkswagen Golf 2019, Honda Civic 2020',
  125000.00,
  NOW()
),

(
  2, 
  2024, 
  'AKEL (Progressive Party of Working People)', 
  'Member of Parliament',
  'Apartment in Larnaca, Land plot in Troodos, Office space in city center',
  'Volkswagen Golf 2019, Honda Civic 2020, Renault Clio 2023',
  135000.00,
  NOW()
),

(
  3, 
  2023, 
  'DEKO (Democratic Party)', 
  'Member of Parliament',
  'House in Limassol, Apartment in Paphos',
  'Audi A4 2021, Hyundai i30 2017',
  98000.00,
  NOW()
),

(
  3, 
  2024, 
  'DEKO (Democratic Party)', 
  'Member of Parliament',
  'House in Limassol, Apartment in Paphos, Retail space in Paphos',
  'Audi A4 2021, Hyundai i30 2017, Tesla Model 3 2024',
  110000.00,
  NOW()
);