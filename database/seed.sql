INSERT INTO users (username, email, password_hash, role, created_at) VALUES
(
  'nikos_christodoulides',
  'nikos@parliament.cy',
  '$2y$10$NdzrXQ3TtN08jHW3dxtndeSuLUM3NTQ9R21OvD.AW93GLgVC8GmZW',
  'politician',
  NOW()
),
(
  'kleovoulos',
  'kleovoulos@gmail.com',
  '$2y$10$9ZP31/NJDB76SQX0SxNqOO3x3cO8Xu3J78fG5EyxL.CO0uCB0purq',
  'citizen',
  NOW()
),
(
  'admin_user',
  'admin@admin.com',
  '$2y$10$hM2qw6Q8zma7SV/nUMX2CeTkCF1XB.4wFn3k8QPIYQGXGkBMjMQIG',
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
    1, 2023, 'DISY (Democratic Rally)', 'President of Cyprus', 'Nicosia',
    'House in Limassol (€350,000), Apartment in Larnaca seafront (€180,000)',
    'Mercedes-Benz S-Class 2020',
    'Bank of Cyprus shares (500 units), CYTA shares (1000 units)',
    15000.00,
    195000.00
),

(
  1, 2022, 'DISY (Democratic Rally)', 'President of Cyprus', 'Nicosia',
  'House in Limassol (€300,000), Apartment in Larnaca seafront (€150,000)',
  'Mercedes-Benz S-Class 2018',
  'Bank of Cyprus shares (500 units), CYTA shares (1000 units)',
  10000.00,
  195000.00
),

(
  1, 2021, 'DISY (Democratic Rally)', 'President of Cyprus', 'Nicosia',
  'House in Limassol (€200,000), Apartment in Larnaca seafront (€100,000)',
  'Mercedes-Benz S-Class 2010',
  'Bank of Cyprus shares (400 units), CYTA shares (1000 units)',
  9000.00,
  195000.00
),

(

  1, 2020, 'DISY (Democratic Rally)', 'President of Cyprus', 'Nicosia',
  'House in Limassol (€150,000), Apartment in Larnaca seafront (€80,000)',
  'Mercedes-Benz S-Class 2005',
  'Bank of Cyprus shares (300 units), CYTA shares (1000 units)',
  8000.00,
  195000.00

);