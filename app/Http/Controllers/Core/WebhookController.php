<?php

namespace App\Http\Controllers\Core;

use App\Dao\Enums\Core\StatusType;
use App\Dao\Models\Order;
use App\Dao\Models\Webhook;
use App\Http\Controllers\Controller;
use ByBit\SDK\Enums\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use ByBit\SDK\ByBitApi;
use ByBit\SDK\Enums\AccountType;
use ByBit\SDK\Enums\OrderType;
use ByBit\SDK\Enums\PositionIdx;
use ByBit\SDK\Enums\Side;
use ByBit\SDK\Enums\TimeInForce;
use GuzzleHttp\Promise\Create;

class WebhookController extends Controller
{
    public $service;

    public function __construct()
    {
        if(empty($this->service))
        {
            $api_key = env('BYBIT_KEY');
            $api_secret = env('BYBIT_SECRET');
            $host = \ByBit\SDK\ByBitApi::PROD_API_URL;

            $bybitApi = new ByBitApi($api_key, $api_secret, $host);
            $this->service = $bybitApi;
        }
    }

    private function getBalance($coin = 'USDT')
    {
        $params = ['accountType' => AccountType::UNIFIED];
        $result = $this->service->accountApi()->getWalletBalance($params);
        if(isset($result['list'][0]['coin']))
        {
            $data_wallet = $result['list'][0]['coin'] ?? [];
            foreach($data_wallet as $wallet)
            {
                if($wallet['coin'] == $coin)
                {
                    if($coin == 'USDT')
                    {
                        return ($wallet['usdValue'] < 1) ? 0 : $wallet['usdValue'];
                    }
                    else
                    {
                        return $wallet;

                        /*
                        data return wallet

                        [
                            "availableToBorrow" => ""
                            "bonus" => "0"
                            "accruedInterest" => "0"
                            "availableToWithdraw" => ""
                            "totalOrderIM" => "0"
                            "equity" => "0.00076624"
                            "totalPositionMM" => "0"
                            "usdValue" => "68.24291474"
                            "unrealisedPnl" => "0"
                            "collateralSwitch" => false
                            "spotHedgingQty" => "0"
                            "borrowAmount" => "0"
                            "totalPositionIM" => "0"
                            "walletBalance" => "0.00076624"
                            "cumRealisedPnl" => "-0.00000179"
                            "locked" => "0"
                            "marginCollateral" => true
                            "coin" => "BTC"
                        ] */
                    }
                }
            }
        }

        return 0;
    }

    public function deploy(Request $request)
    {
        $githubPayload = $request->getContent();
        $githubHash = $request->header('X-Hub-Signature');
        $localToken = env('GITHUB_WEBHOOK_SECRET');
        $localHash = 'sha1='.hash_hmac('sha1', $githubPayload, $localToken, false);
        if (hash_equals($githubHash, $localHash)) {

            chdir(base_path());
            $process = new Process(['git', 'pull']);
            $process->run();

            // executes after the command finishes
            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return $process->getOutput();
        }
    }

    public function tradingview(Request $request)
    {
        Log::info($request->all());
        $data = file_get_contents('php://input');
        $json = json_decode($data);

        if($json->name)
        {
            $name = $json->name ?? null;
            $status = $json->status ?? null;
            $date = date('Y-m-d h:i:s') ?? null;
            $coin = $json->ticker ?? null;
            $open = $json->open ?? null;
            $close = $json->close ?? null;
            $close = $json->close ?? null;
            $high = $json->high ?? null;
            $low = $json->low ?? null;

            Webhook::create([
                'webhook_data' => json_encode($data) ?? null,
                'webhook_name' => $name,
                'webhook_status' => $status,
                'webhook_time' => $date,
                'webhook_coin' => $coin,
                'webhook_open' => $open,
                'webhook_close' => $close,
                'webhook_high' => $high,
                'webhook_low' => $low,
            ]);

            $order = $this->checkOrder($name, $coin, $status);

            if(in_array($status, [StatusType::Buy, StatusType::Sell]))
            {
                $balance = $this->getBalance();
                $qty = $balance * 20/100;
                $open = 0;
                $category = Category::SPOT;
                $side = StatusType::Buy;
                $type = OrderType::LIMIT;

                $orderLinkId = uniqid();

                Order::create([
                    'order_name' => $name,
                    'order_coin' => $coin,
                    'order_category' => $category,
                    'order_side' => $side,
                    'order_type' => $type,
                    'order_qty' => $qty,
                    'order_open' => $open,
                    'order_reference' => $orderLinkId,
                    'order_date' => $date,
                ]);

                $params = [
                    "category" => $category,
                    "symbol" => $coin,
                    "side" => $side,
                    "positionIdx" => PositionIdx::ONE_WAY_MODE,
                    "orderType" => $type,
                    "qty" => $qty,
                    "price" => $open,
                    "timeInForce" => TimeInForce::GTC,
                    "orderLinkId" => $orderLinkId
                ];

                // $order = $this->service->tradeApi()->placeOrder($params);
            }
        }
    }

    private function checkOrder($name, $coin, $status)
    {
        $order = Order::query()
            ->where('order_name', $name)
            ->where('order_coin', $coin)
            ->where('order_status', $status)
            ->orderBy('order_date', 'DESC')
            ->first();

        if(in_array($status, [StatusType::Buy, StatusType::Sell]))
        {
            if((empty($order) || !empty($order->order_close)) && $status == StatusType::Buy)
            {
                //buy
            }
            elseif(!empty($order) && empty($order->order_close) && $status == StatusType::Sell)
            {
                //sell
            }
        }

    }

    private function createOrder()
    {


        // Get Account Info
        // $params = ['accountType' => AccountType::UNIFIED];
        // $result = $bybitApi->accountApi()->getWalletBalance($params);

        $orderLinkId = uniqid();
        $params = [
            "category" => Category::SPOT,
            "symbol" => "AI16ZUSDT",
            "side" => Side::BUY,
            "positionIdx" => PositionIdx::ONE_WAY_MODE,
            "orderType" => OrderType::LIMIT,
            "qty" => "0.001",
            "price" => "10000",
            "timeInForce" => TimeInForce::GTC,
            "orderLinkId" => $orderLinkId
        ];

        $order = $bybitApi->tradeApi()->placeOrder($params);
    }
}
