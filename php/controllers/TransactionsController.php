<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransactionsController{
  
  private function db(){
    return mysqli_connect('my_mariadb', 'root', 'ciccio', 'bank');
  }

  private function getBalance($db, $accountId) {
      $sql = "SELECT SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END) as balance 
              FROM transactions WHERE account_id = ?";
      $stmt = $db->prepare($sql);
      $stmt->bind_param('i', $accountId);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
      return (float)($row['balance'] ?? 0);
  }

  public function list($req, $res, $args){
    $db = $this->db();
    $accountId = (int)$args['id'];

    $sql = "SELECT id, type, amount, description, created_at
        FROM transactions
        WHERE account_id = ?
        ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    $res->getBody()->write(json_encode($transactions));
    return $res->withHeader('Content-Type', 'application/json');
  }

  public function detail($req, $res, $args){
    $db = $this->db();
    $transactionId = (int)$args['idT'];

    $sql = "SELECT * FROM transactions WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();

    if (!$transaction) {
      $res->getBody()->write(json_encode(['error' => 'Not found']));
      return $res->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $res->getBody()->write(json_encode($transaction));
    return $res->withHeader('Content-Type', 'application/json');
    
  }

  public function deposit($req, $res, $args){
    $db = $this->db();
    $accountId = (int)$args['id'];

    $data = json_decode($req->getBody(), true);
    $amount = (float)($data['amount'] ?? 0);
    $description = $data['description'] ?? '';

    if ($amount <= 0) {
      $res->getBody()->write(json_encode(["error" => "Invalid amount"]));
      return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $balance = $this->getBalance($db, $accountId);
    $newBalance = $balance + $amount;

    $sql = "INSERT INTO transactions (account_id, type, amount, description, balance_after)
        VALUES (?, 'deposit', ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('idsd', $accountId, $amount, $description, $newBalance);
    $stmt->execute();

    $res->getBody()->write(json_encode([
      "message" => "Deposit ok",
      "balance" => $newBalance
    ]));

    return $res->withStatus(201)->withHeader('Content-Type', 'application/json');
  }

  public function withdrawal($req, $res, $args){
    $db = $this->db();
    $accountId = (int)$args['id'];

    $data = json_decode($req->getBody(), true);
    $amount = (float)($data['amount'] ?? 0);
    $description = $data['description'] ?? '';

    if ($amount <= 0) {
      $res->getBody()->write(json_encode(["error" => "Invalid amount"]));
      return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $balance = $this->getBalance($db, $accountId);

    if ($amount > $balance) {
      $res->getBody()->write(json_encode(["error" => "Insufficient balance"]));
      return $res->withStatus(422)->withHeader('Content-Type', 'application/json');
    }

    $newBalance = $balance - $amount;

    $sql = "INSERT INTO transactions (account_id, type, amount, description, balance_after)
        VALUES (?, 'withdrawal', ?, ?, ?)";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('idsd', $accountId, $amount, $description, $newBalance);
    $stmt->execute();

    $res->getBody()->write(json_encode([
      "message" => "Withdrawal ok",
      "balance" => $newBalance
    ]));

    return $res->withStatus(201)->withHeader('Content-Type', 'application/json');
  }

  public function update($req, $res, $args){
    $db = $this->db();
    $transactionId = (int)$args['idT'];

    $data = json_decode($req->getBody(), true);
    $description = $data['description'] ?? '';

    $sql = "UPDATE transactions SET description = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('si', $description, $transactionId);
    $stmt->execute();

    $res->getBody()->write(json_encode(["message" => "Updated"]));
    return $res->withHeader('Content-Type', 'application/json');
  }

  public function delete($req, $res, $args){
    $db = $this->db();
    $accountId = (int)$args['id'];
    $transactionId = (int)$args['idT'];

    $sqlLast = "SELECT id FROM transactions
                WHERE account_id = ?
                ORDER BY created_at DESC
                LIMIT 1";

    $stmt = $db->prepare($sqlLast);
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();

    if (!$last || $last['id'] != $transactionId) {
      $res->getBody()->write(json_encode(["error" => "Only last transaction can be deleted"]));
      return $res->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    $sqlDelete = "DELETE FROM transactions WHERE id = ?";
    $stmt = $db->prepare($sqlDelete);
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();

        $res->getBody()->write(json_encode(["message" => "Deleted"]));
        return $res->withHeader('Content-Type', 'application/json');
    }

    public function balance($req, $res, $args) {
        $db = $this->db();
        $accountId = (int)$args['id'];
        
        $balance = $this->getBalance($db, $accountId);
        
        //Prende la valuta del conto
        $stmt = $db->prepare("SELECT currency FROM accounts WHERE id = ?");
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $currency = $account['currency'] ?? 'EUR';

        $res->getBody()->write(json_encode([
            'account_id' => $accountId,
            'balance' => $balance,
            'currency' => $currency
        ]));
        return $res->withHeader('Content-Type', 'application/json');
    }

    //Conversione fiat
    public function convertFiat($req, $res, $args) {
        $db = $this->db();
        $accountId = (int)$args['id'];
        
        $params = $req->getQueryParams();
        $to = strtoupper($params['to'] ?? '');
        
        if (!$to) {
            $res->getBody()->write(json_encode(['error' => 'Missing currency parameter']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $balance = $this->getBalance($db, $accountId);
        
        //Chiamata frankfurter
        $url = "https://api.frankfurter.dev/v1/latest?base=EUR&symbols={$to}";
        $json = @file_get_contents($url);
        
        if (!$json) {
            $res->getBody()->write(json_encode(['error' => 'API error']));
            return $res->withStatus(502)->withHeader('Content-Type', 'application/json');
        }
        
        $data = json_decode($json, true);
        
        if (!isset($data['rates'][$to])) {
            $res->getBody()->write(json_encode(['error' => 'Currency not supported']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $rate = $data['rates'][$to];
        $converted = round($balance * $rate, 2);
        
        $res->getBody()->write(json_encode([
            'account_id' => $accountId,
            'from_currency' => 'EUR',
            'to_currency' => $to,
            'original_balance' => $balance,
            'converted_balance' => $converted,
            'rate' => $rate
        ]));
        return $res->withHeader('Content-Type', 'application/json');
    }

    //Conversione crypto
    public function convertCrypto($req, $res, $args) {
        $db = $this->db();
        $accountId = (int)$args['id'];
        
        $params = $req->getQueryParams();
        $to = strtoupper($params['to'] ?? '');
        
        if (!$to) {
            $res->getBody()->write(json_encode(['error' => 'Missing crypto parameter']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $balance = $this->getBalance($db, $accountId);
        
        
        $symbol = $to . 'EUR';
        
        // Chiamata binance
        $url = "https://api.binance.com/api/v3/ticker/price?symbol={$symbol}";
        $json = @file_get_contents($url);
        
        if (!$json) {
            $res->getBody()->write(json_encode(['error' => 'Crypto pair not found']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $data = json_decode($json, true);
        
        if (!isset($data['price'])) {
            $res->getBody()->write(json_encode(['error' => 'Price not available']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $price = (float)$data['price'];
        $converted = round($balance / $price, 8);
        
        $res->getBody()->write(json_encode([
            'account_id' => $accountId,
            'from_currency' => 'EUR',
            'to_crypto' => $to,
            'original_balance' => $balance,
            'converted_amount' => $converted,
            'price' => $price
        ]));
        return $res->withHeader('Content-Type', 'application/json');
    }
}