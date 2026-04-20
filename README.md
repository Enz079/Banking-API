Schema del Database Il database (MySQL/MariaDB) è composto da due tabelle principali per garantire la tracciabilità di ogni operazione

\-- Tabella dei conti CREATE TABLE accounts ( id INT AUTO\_INCREMENT PRIMARY KEY, owner\_name VARCHAR(255) NOT NULL, currency VARCHAR(3) DEFAULT 'EUR', created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP );

\-- Tabella dei movimenti CREATE TABLE transactions ( id INT AUTO\_INCREMENT PRIMARY KEY, account\_id INT NOT NULL, amount DECIMAL(15, 2) NOT NULL, description TEXT, balance\_after DECIMAL(15, 2), created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP, FOREIGN KEY (account\_id) REFERENCES accounts(id) ); 2. Scelte progettuali Calcolo del saldo: Il saldo non è un valore statico. Viene calcolato dinamicamente come differenza tra la somma dei depositi e la somma dei prelievi

Integrità dei dati: I prelievi sono autorizzati solo se l'importo è disponibile (il saldo non può andare in negativo)

Limitazioni PUT: Per preservare lo storico contabile, l'endpoint di modifica agisce solo sul campo description

Integrazione esterna: Utilizzo di Frankfurter per i tassi fiat e Binance Spot API per le quotazioni crypto (con precisione fino a 8 decimali)

Ambiente Docker: Il progetto è predisposto per essere avviato tramite Docker

Endpoint e Esempi di Chiamata Metodo Endpoint Descrizione GET/accounts/1/balance Visualizza il saldo attuale POST/accounts/1/deposits Registra un nuovo deposito POST/accounts/1/withdrawals Registra un nuovo prelievo GET/accounts/1/transactions Elenco completo dei movimenti PUT/accounts/1/transactions/{id} Modifica descrizione movimento GET/accounts/1/balance/convert/fiat Conversione saldo (es. USD) GET/accounts/1/balance/convert/crypto Conversione saldo (es. BTC)

Esempi di chiamata (curl) Registrare un deposito: curl -X POST http://localhost/accounts/1/deposits

\-H "Content-Type: application/json"

\-d '{"amount": 500.00, "description": "Accredito stipendio"}' Eseguire un prelievo: curl -X POST http://localhost/accounts/1/withdrawals

\-H "Content-Type: application/json"

\-d '{"amount": 50.00, "description": "Prelievo ATM"}' Conversione in Dollari (Fiat): curl -X GET "http://localhost/accounts/1/balance/convert/fiat?to=USD" Conversione in Bitcoin (Crypto): curl -X GET "http://localhost/accounts/1/balance/convert/crypto?to=BTC" 4. Gestione Errori L'API risponde con codici standard per garantire chiarezza: 400 Bad Request: dati mancanti o formati non validi. 404 Not Found: donto o movimento inesistente. 422 Unprocessable entity: violazione regole di business (es. saldo insufficiente per prelievo). 502 Bad Gateway: Errore di comunicazione con Frankfurter o Binance.