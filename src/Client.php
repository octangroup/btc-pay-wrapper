<?php

namespace BTCPay;

require_once 'contracts/BTCPayerClient.php';

use \GuzzleHttp;
use \BTCPay\Contracts\BTCPayerClient;

class Client implements BTCPayerClient
{

  protected $cryptoCode = 'BTC';
  protected $host = '';
  protected $client;
  protected $storeId = '';
  protected $connected = false;
  protected $apiKey = '';
  protected $invoiceExpiration = 600;

  public function __construct($host, $apiKey, $storeId)
  {
    $this->host = $host;
    $this->apiKey = $apiKey;
    $this->storeId = $storeId;
  }

  private function request($method, $path, $body = null)
  {
    $headers = [
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
      'Access-Control-Allow-Origin' => '*',
      'Authorization' => 'token ' . $this->apiKey
    ];

    $requestBody = $body ? json_encode($body) : null;
    $request = new GuzzleHttp\Psr7\Request($method, "/api/v1/$path", $headers, $requestBody);
    $response = $this->client()->send($request);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
      $responseBody = $response->getBody()->getContents();
      return json_decode($responseBody, true);
    } else {
      // raise exception
    }
  }

  public function init()
  {
    return $this->authorize();
  }

  private function authorize()
  {
    $data = $this->request("GET", "api-keys/current");
    $this->connected = true;
    return $data;
  }

  public function getInfo(): array
  {

    $data = $this->request("GET", "server/info");
    return [
      'alias' => $data['version'],
      'identity_pubkey' => $data['onion'],
    ];
  }

  public function getBalance()
  {
    return ['balance' => null];
  }

  private function client()
  {
    if ($this->client) {
      return $this->client;
    }
    $options = ['base_uri' => $this->host];
    $this->client = new GuzzleHttp\Client($options);
    return $this->client;
  }

  public function isConnectionValid(): bool
  {
    return $this->connected;
  }

  public function addInvoice($invoice): array
  {
    $storeId = $this->storeId;
    $cryptoCode = $this->cryptoCode;
    
    // btc pay server expects the amount to be in mSats
    $amount = ((int)$invoice['value']) * 1000;
    $data = $this->request("POST", "stores/$storeId/lightning/$cryptoCode/invoices", [
      'amount' => $amount,
      'description' => $invoice['memo'],
      "expiry" => $this->invoiceExpiration,
    ]);
    $data['r_hash'] = $data['id'];
    $data['payment_request'] = $data['BOLT11'];
    return $data;
  }

  public function getInvoice($checkingId): array
  {
    $storeId = $this->storeId;
    $cryptoCode = $this->cryptoCode;

    $invoice = $this->request("GET", "stores/$storeId/lightning/$cryptoCode/invoices/$checkingId");
    $invoice['r_hash'] = $invoice['id'];
    $invoice['payment_request'] = $invoice['BOLT11'];

    $invoice['settled'] = $invoice['paidAt'] ? true : false; //kinda mimic lnd
    return $invoice;
  }

  public function isInvoicePaid($checkingId): bool
  {
    $invoice = $this->getInvoice($checkingId);
    return $invoice['settled'];
  }
}
