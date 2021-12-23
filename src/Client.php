<?php

namespace BTCPay;

require_once 'contracts/BTCPayerClient.php';

use \GuzzleHttp;
use \BTCPay\Contracts\BTCPayerClient;

class Client implements BTCPayerClient
{

  protected $cryptoCode = '';
  protected $host = '';
  protected $client;
  protected $storeId = '';
  protected $connected = false;

  public function __construct($host, $cryptoCode, $storeId)
  {
    $this->host = $host;
    $this->cryptoCode = $cryptoCode;
    $this->storeId = $storeId;
  }

  private function request($method, $path, $body = null)
  {
    $headers = [
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
      'Access-Control-Allow-Origin' => '*',
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
    $cryptoCode = $this->cryptoCode;
    $data = $this->request("GET", "server/lightning/$cryptoCode/connect");
    return $data;
  }

  public function getInfo(): array
  {
    $storeId = $this->storeId;
    $cryptoCode = $this->cryptoCode;
    $data = $this->request("GET", "stores/$storeId/lightning/$cryptoCode/info");
    return $data;
  }

  public function getBalance()
  {
    return 0;
  }

  private function client()
  {
    if ($this->client) {
      return $this->client;
    }
    $options = ['base_uri' => $this->url];
    $this->client = new GuzzleHttp\Client($options);
    return $this->client;
  }

  public function isConnectionValid(): bool
  {
    return !empty($this->access_token);
  }

  public function addInvoice($invoice): array
  {
    $storeId = $this->storeId;
    $cryptoCode = $this->cryptoCode;
    $data = $this->request("POST", "stores/$storeId/lightning/$cryptoCode/invoices", [
      'amount' => $invoice['value'],
      'description' => $invoice['memo']
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
