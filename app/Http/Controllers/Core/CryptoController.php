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
use Telegram\Bot\Laravel\Facades\Telegram;

class CryptoController extends Controller
{
    public $service;

    public function __construct()
    {
        if(empty($this->service))
        {
            $api_key = env('BYBIT_KEY');
            $api_secret = env('BYBIT_SECRET');
            $host = env('BYBIT_ENV', 'dev') == 'dev' ? \ByBit\SDK\ByBitApi::TESTNET_API_URL : \ByBit\SDK\ByBitApi::PROD_API_URL;

            $bybitApi = new ByBitApi($api_key, $api_secret, $host);
            $this->service = $bybitApi;
        }
    }

    private function getLastDecimalPrice($value)
    {
        $explode = explode('.', $value);
        if(isset($explode[1]))
        {
            $last = mb_substr($explode[1], -1) + 1;
            $value = substr_replace($value, $last, -1);
        }
        else
        {
            $last = mb_substr($value, -1) + 1;
            $value = substr_replace($value, $last, -1);
        }

        return $value;

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

    private function getPriceBuy($value, $percent = 0.2)
    {
        if($percent == 0)
        {
            return $this->getLastDecimalPrice($value);
        }

        $price = $value - ($value * ($percent / 100));
        return $price;
    }

    private function getPriceSell($value, $percent = 0.2)
    {
        if($percent == 0)
        {
            return $this->getLastDecimalPrice($value);
        }

        $price = $value + ($value * $percent / 100);
        return $price;
    }

    private function getPrice($coin, $side)
    {
        $params = [
            'symbol' => $coin,
            'category' => Category::LINEAR,
        ];

        $price_data = $this->service->marketApi()->getTickers($params);
        $price = $price_data['list'][0]['lastPrice'];

        if($side == Side::BUY)
        {
            return $this->getPriceBuy($price, 0);
        }
        else
        {
            return $this->getPriceSell($price, 0);
        }
    }

    public function tradingview(Request $request)
    {
        $data = file_get_contents('php://input');
        $json = json_decode($data);

        if($json->name)
        {
            $name = $json->name ?? null;
            $side = $json->side ?? null;
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
                'webhook_side' => $side,
                'webhook_time' => $date,
                'webhook_coin' => $coin,
                'webhook_open' => $open,
                'webhook_close' => $close,
                'webhook_high' => $high,
                'webhook_low' => $low,
            ]);

            $price = $close;
            $order = $this->checkOrder($coin, $side, $price);
        }
    }

    /* $data = [
        "symbol" => "BTCUSDT"
        "leverage" => "10"
        "autoAddMargin" => 0
        "avgPrice" => "73886.2"
        "liqPrice" => "66867.1"
        "riskLimitValue" => "2000000"
        "takeProfit" => "82250"
        "positionValue" => "147.7724"
        "isReduceOnly" => false
        "tpslMode" => "Full"
        "riskId" => 1
        "trailingStop" => "0"
        "unrealisedPnl" => "12.64178"
        "markPrice" => "80207.09"
        "adlRankIndicator" => 2
        "cumRealisedPnl" => "-3.11101326"
        "positionMM" => "0.81200934"
        "createdTime" => "1740535604890"
        "positionIdx" => 0
        "positionIM" => "14.85038734"
        "seq" => 9432962655
        "updatedTime" => "1740575831223"
        "side" => "Buy"
        "bustPrice" => ""
        "positionBalance" => "14.85038734"
        "leverageSysUpdatedTime" => ""
        "curRealisedPnl" => "-0.02955448"
        "size" => "0.002"
        "positionStatus" => "Normal"
        "mmrSysUpdatedTime" => ""
        "stopLoss" => "70037.5"
        "tradeMode" => 0
        "sessionAvgPrice" => ""
    ]; */

    private function getPosition($coin, $raw = false)
    {
        $params = ["category" => Category::LINEAR, "symbol" => $coin];

        try {
            $position = $this->service->positionApi()->getPositionInfo($params);

            if(isset($position['list'][0][$raw]) && is_string($raw))
            {
                return $this->json(true, $position[$raw]);
            }

            if($raw == true)
            {
                return $this->json(true, $position);
            }

            if(isset($position['list'][0]['positionValue']) && !empty($position['list'][0]['positionValue']))
            {
                return $this->json(true, $position['list'][0]['markPrice']);
            }

            return $this->json(false, $position);

        } catch (\Throwable $th) {

            $this->json(false, $th->getMessage());
        }

    }

    private function getQty($amount, $price)
    {
        $ten_percent = $amount * 20/100;
        return (doubleval($ten_percent) * intval(env('LEVERAGE', 1))) / doubleval($price);
    }

    private function checkOrder($coin, $side)
    {
        $position = $this->getPosition($coin);

        if(in_array($side, [StatusType::Buy, StatusType::Sell]))
        {
            if($position['status'] && $side == StatusType::Sell)
            {
                return $this->closePosition($coin, $side, $position['data']);
            }
            else
            {
                $booking = $this->getOrder($coin);

                if($booking['status'] == false && $side == StatusType::Buy)
                {
                    return $this->createOrder($coin, Side::BUY);
                }
                else if($booking['status'] && $side == StatusType::Sell && !empty(strtolower($booking['data'])))
                {
                    return $this->closeOrder($coin, $booking['data']);
                }
            }
        }
        else
        {
            if($position['status'] && $side == StatusType::Close)
            {
                return $this->closePosition($coin, $side, $position['data']);
            }
            else
            {
                $booking = $this->getOrder($coin);

                if($booking['status'] == false && $side == StatusType::Open)
                {
                    return $this->createOrder($coin, Side::SELL);
                }
                else if($booking['status'] && $side == StatusType::Close && !empty(strtolower($booking['data'])))
                {
                    return $this->closeOrder($coin, $booking['data']);
                }
            }
        }

        return false;
    }

    private function closePosition($coin, $side, $base_price)
    {
        $price = $this->getPrice($coin, $side);

        if($side == StatusType::Sell)
        {
            $take_profit = $this->getPriceSell($price, 0);
            $stop_loss = $this->getPriceBuy($price, 0);
        }
        else
        {
            $take_profit = $this->getPriceBuy($price, 0);
            $stop_loss = $this->getPriceSell($price, 0);
        }

        $this->updatedOrder($coin, $take_profit);

        $params = ["category" => Category::LINEAR, "symbol" => $coin, "takeProfit" => strval($take_profit), "stopLoss" => strval($stop_loss)];
        if(env('TRADE', false))
        {
            $order = $this->service->positionApi()->setTradingStop($params);
        }
    }

    private function closeOrder($coin, $reference)
    {
        $params = ["category" => Category::LINEAR, "symbol" => $coin, "orderId" => $reference];
        if(env('TRADE', false))
        {
            $order = $this->service->tradeApi()->cancelOrder($params);
        }

        $this->updatedOrder($coin);
    }

    private function getOrder($coin)
    {
        try
        {
            $params = ["category" => Category::LINEAR, "symbol" => $coin, "openOnly" => 0, "orderFilter" => "Order"];
            $order = $this->service->tradeApi()->getOpenOrders($params);

            if(isset($order['list'][0]['orderId']) && !empty($order['list'][0]['orderId']))
            {
                return $this->json(true, $order['list'][0]['orderId']);
            }
            else
            {
                return $this->json(false, $order);
            }
        }
        catch (\Throwable $th) {
            $th->getMessage();
            return $this->json(false, $th->getMessage());
        }

        return $order;
    }

    private function getDirection($side)
    {
        $side == Side::BUY ? Side::BUY : Side::SELL;
        return $side;
    }

    private function createOrder($coin, $side)
    {
        $price = $this->getPrice($coin, $side);

        $direction = $this->getDirection($side);
        if($direction == Side::BUY)
        {
            $take_provit = $price + (($price * 5) / 100); //80%
            $stop_loss = $price - (($price * 0.3) / 100); //2%
        }
        else
        {
            $take_provit = $price - (($price * 5) / 100); //80%
            $stop_loss = $price + (($price * 0.3) / 100); //2%
        }

        $amount = $this->getBalance();
        $qty = $this->getQty($amount, $price);

        $orderLinkId = uniqid();

        $params = [
            "category" => Category::LINEAR,
            "symbol" => $coin,
            "side" => $direction,
            "orderType" => OrderType::LIMIT,
            "qty" => strval(round($qty, 3)),
            "price" => strval(round($price, 3)),
            "timeInForce" => TimeInForce::GTC,
            "positionIdx" => PositionIdx::ONE_WAY_MODE,
            "orderLinkId" => $orderLinkId,
            "reduceOnly" => false,
            "takeProfit" => strval(round($take_provit, 3)),
            "stopLoss" => strval(round($stop_loss, 3)),
            "tpslMode" => "Full",
        ];

        try {
            if(env('TRADE', false))
            {
                $order = $this->service->tradeApi()->placeOrder($params);
            }

        if(isset($order['orderId']))
        {
            Order::create([
                'order_category' => Category::LINEAR,
                'order_coin' => $order['orderId'],
                'order_reference' => $order['orderId'],
                'order_code' => $order['orderLinkId'],
                'order_side' => $side,
                'order_qty' => $side,
                'order_open' => $price,
                'order_date' => date('Y-m-d H:i:s'),
                'order_status' => StatusType::Draft,
            ]);

            return true;
        }
        else
        {
            Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_ID'),
                'text' => json_encode($params),
            ]);

            return false;
        }

        } catch (\Throwable $th) {
            Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_ID'),
                'text' => $th->getMessage(),
            ]);

            return false;
        }

        return false;
    }

    private function updatedOrder($coin, $price = 0)
    {
        $order = Order::query()
            ->where('order_coin', $coin)
            ->whereNull('order_close')
            ->orderBy('order_date', 'DESC')
            ->first();

        if($order)
        {
            $order->order_close = $price;
            $order->close = $price;
            $order->save();
        }
    }

    private function json($boolean, $data)
    {
        return [
            "status" => $boolean,
            "data" => $data
        ];
    }
}
