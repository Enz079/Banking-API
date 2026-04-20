# Mini Banking API

API REST per la gestione di un conto bancario semplificato con supporto per depositi, prelievi, movimenti e conversioni di valuta (fiat e crypto).

## Struttura del progetto

mini-banking-api/

├── index.php # Entry point dell'applicazione

├── TransactionsController.php # Controller principale

├── config/

│ └── database.php # Configurazione database

├── docker-compose.yml # Configurazione Docker

├── Dockerfile # Dockerfile per PHP

└── README.md # Questo file


## Installazione e avvio con Docker

```bash
# Clona il repository
git clone <repository-url>
cd mini-banking-api

# Avvia i container
docker-compose up -d

# Installa le dipendenze PHP
docker exec -it mini-banking-php composer install

## Database

CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(255) NOT NULL,
    currency VARCHAR(3) DEFAULT 'EUR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    balance_after DECIMAL(15, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);


## Endpoint

GET    /accounts/{id}/transactions
GET    /accounts/{id}/transactions/{idT}
POST   /accounts/{id}/deposits
POST   /accounts/{id}/withdrawals
PUT    /accounts/{id}/transactions/{idT}
DELETE /accounts/{id}/transactions/{idT}
GET    /accounts/{id}/balance
GET    /accounts/{id}/balance/convert/fiat?to={currency}
GET    /accounts/{id}/balance/convert/crypto?to={crypto}

## Esempi di chiamata

1. Visualizzare il saldo: curl -X GET http://localhost:8080/accounts/1/balance

2. Registrare un deposito:curl -X POST http://localhost:8080/accounts/1/deposits \
  -H "Content-Type: application/json" \
  -d '{"amount": 500.00, "description": "Accredito stipendio"}'

3. Eseguire un prelievo: curl -X POST http://localhost:8080/accounts/1/withdrawals \
  -H "Content-Type: application/json" \
  -d '{"amount": 50.00, "description": "Prelievo ATM"}'

4. Elenco movimenti: curl -X GET http://localhost:8080/accounts/1/transactions

5. Dettaglio movimento: curl -X GET http://localhost:8080/accounts/1/transactions/1

6. Modificare descrizione movimento: curl -X PUT http://localhost:8080/accounts/1/transactions/1 \
  -H "Content-Type: application/json" \
  -d '{"description": "Stipendio Gennaio 2024"}'

7. Eliminare ultimo movimento: curl -X DELETE http://localhost:8080/accounts/1/transactions/2