
<?php

require_once 'vendor/autoload.php';

use BTCPay\Client;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    private function client(): Client
    {
        $host = 'https://btcpay0.voltageapp.io';
        $apiKey = 'bc55dbb023e9fe6562f7e8bf8292076b6bdf332d';
        $password = 'AgC2GREtNAmSU8QVhaU87x8dgywHtRF1d1txGevJ3BPu';
        return new Client($host, $apiKey, $password);
    }
    public function testCanBeInitialized(): void
    {
        $this->assertInstanceOf(
            Client::class,
            $this->client(),
        );
    }

    public function testCanAuthorize(): void
    {
        $client = $this->client();
        $this->assertFalse($client->isConnectionValid());
        $client->init();
        $this->assertTrue($client->isConnectionValid());
    }

    // public function testCanGetBalance(): void
    // {
    //     $client = $this->client();
    //     $client->init();
    //     $this->assertIsNumeric($client->getBalance()['balance']);
    // }

    // public function testCanGetInfo(): void
    // {
    //     $client = $this->client();
    //     $client->init();
    //     $this->assertIsString($client->getInfo()['alias']);
    // }

    public function testCanAddInvoice(): void
    {
        $client = $this->client();
        $client->init();
        $response = $client->addInvoice([
            'value' => 23,
            'memo' => 'test invoice'
        ]);
        $this->assertIsString($response['payment_request']);
        $this->assertIsString($response['r_hash']);
    }

    public function testCanGetInvoice(): void
    {
        $client = $this->client();
        $client->init();
        $response = $client->addInvoice([
            'value' => 23,
            'memo' => 'test invoice'
        ]);
        $invoice = $client->getInvoice($response['r_hash']);

        $this->assertArrayHasKey('settled', $invoice);
    }
}
