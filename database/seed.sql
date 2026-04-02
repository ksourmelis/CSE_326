INSERT INTO users (username, email, password_hash, role, created_at) VALUES
(
  'nikos_christodoulides',
  'nikos@parliament.cy',
  '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DH6IBi',
  'politician',
  NOW()
),
(
  'anna_mavrides',
  'anna@parliament.cy',
  '$2y$10$V4rMZCwIKWN9/wvp7fhVx.UDpZHVGMNlHHy2JjLFyJ2VqXH6/Ycri',
  'politician',
  NOW()
),
(
  'george_papadopoulos',
  'george@parliament.cy',
  '$2y$10$abcDefGhIjKlMnOpQrStUvWxYzAbCdEfGhIjKlMnOpQrStUvWxYz',
  'politician',
  NOW()
),
(
  'admin_user',
  'admin@parliament.cy',
  '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DH6IBi',
  'admin',
  NOW()
);


INSERT INTO declarations (
    user_id, declaration_year, party, position, province,
    properties, vehicles, shares, debts, income
) VALUES

(
    1, 2024, 'DISY (Democratic Rally)', 'President of Parliament', 'Nicosia',
    'House in Engomi (€450,000), Apartment in Larnaca seafront (€280,000), Commercial space in Limassol (€150,000)',
    'Mercedes-Benz S-Class 2022, Toyota Yaris 2018, BMW 3 Series 2023',
    'Bank of Cyprus shares (500 units), CYTA shares (1000 units)',
    25000.00,
    195000.00
),

(
    2, 2024, 'AKEL (Progressive Party of Working People)', 'Member of Parliament', 'Larnaca',
    'Apartment in Larnaca (€220,000), Land plot in Troodos (€40,000), Office space in city center (€95,000)',
    'Volkswagen Golf 2019, Honda Civic 2020, Renault Clio 2023',
    'Hellenic Bank shares (300 units), Alpha Bank shares (200 units)',
    15000.00,
    135000.00
),

(
    3, 2024, 'DEKO (Democratic Party)', 'Member of Parliament', 'Limassol',
    'House in Limassol (€380,000), Apartment in Paphos (€160,000), Retail space in Paphos (€120,000)',
    'Audi A4 2021, Hyundai i30 2017, Tesla Model 3 2024',
    'CYTA shares (600 units), Marfin shares (150 units)',
    45000.00,
    110000.00
);