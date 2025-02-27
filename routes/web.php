<?php

use App\Dao\Enums\Core\MenuType;
use App\Dao\Enums\Core\NotificationType;
use App\Http\Controllers\Core\HomeController;
use App\Http\Controllers\PublicController;
use Buki\AutoRoute\AutoRouteFacade as AutoRoute;
use ByBit\SDK\ByBitApi;
use ByBit\SDK\Enums\Category;
use ByBit\SDK\Enums\OrderType;
use ByBit\SDK\Enums\PositionIdx;
use ByBit\SDK\Enums\Side;
use ByBit\SDK\Enums\TimeInForce;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Plugins\Query;
use \Lin\Bybit\BybitV5;

Route::get('console', [HomeController::class, 'console'])->name('console');
Route::get('test', function () {


//     $ably   = new \Ably\AblyRest(env('ABLY_KEY'));

//     $channel = $ably->channels->get('private-broadcast');
//   $channel->publish('bell', 'test');

});

Route::get('/signout', 'App\Http\Controllers\Auth\LoginController@logout')->name('signout');
Route::get('/home', 'App\Http\Controllers\Core\HomeController@index')->middleware(['access'])->name('home');
Route::get('/delete/{code}', 'App\Http\Controllers\Core\HomeController@delete')->middleware(['access'])->name('delete_url');
Route::get('/doc', 'App\Http\Controllers\Core\HomeController@doc')->middleware(['access'])->name('doc');

Route::match (['POST', 'GET'], 'change-password', 'App\Http\Controllers\Core\UserController@changePassword', ['name' => 'change-password'])->middleware('auth');
Route::get('profile', 'App\Http\Controllers\Core\UserController@getProfile')->middleware('auth')->name('getProfile');
Route::post('profile', 'App\Http\Controllers\Core\UserController@updateProfile')->middleware('auth')->name('updateProfile');
Auth::routes(['verify' => true]);

Route::get('/', [PublicController::class, 'index'])->name('public');
Route::post('/checkout', [PublicController::class, 'checkout'])->middleware('auth')->name('checkout');

try {
    $routes = Query::groups();
} catch (\Throwable $th) {
    $routes = [];
}

if ($routes) {
    Route::middleware(['auth', 'access'])->group(function () use ($routes) {
        Route::prefix('admin')->group(function () use ($routes) {
            if ($routes) {
                foreach ($routes as $group) {
                    Route::group(['prefix' => $group->field_primary, 'middleware' => [
                        'auth',
                        'access',
                    ]], function () use ($group) {
                        // -- nested group
                        if ($menus = $group->has_menu) {
                            foreach ($menus as $menu) {

                                if ($menu->field_type == MenuType::Menu) {

                                    Route::group(['prefix' => 'default'], function () use ($menu) {
                                        try {
                                            AutoRoute::auto($menu->field_url, $menu->field_controller, ['name' => $menu->field_primary]);
                                        } catch (\Throwable $th) {
                                            //throw $th;
                                        }
                                    });

                                } elseif ($menu->field_type == MenuType::Group) {

                                    if ($links = $menu->has_link) {
                                        Route::group(['prefix' => $menu->field_url], function () use ($links) {
                                            foreach ($links as $link) {

                                                try {
                                                    AutoRoute::auto($link->field_url, $link->field_controller, ['name' => $link->field_primary]);
                                                } catch (\Throwable $th) {
                                                    //throw $th;
                                                }

                                            }
                                        });
                                    }
                                }
                            }
                        }
                        // end nested group

                    });
                }
            }
        });
    });
}


Route::get('testing', function () {

    dd(end(str_split(0.0001)));

    // $key = "cbce6a0e78e24e25d97fdb43441dae7b0fbb946d7b17e79bb5f3826b5f25e57b";
    // $secret = "d4e19af62ce6795d39eaf18292c4d4a967f7a83b8491b7efec5ace27b48a7ef9";

    // $api = new Binance\API($key,$secret, true);

    // $prices = $api->prices();
    // $price = $api->price('BTCUSDT');

    // $open = doubleval($price) - (doubleval($price) * 0.1 / 100); //1%

    // $quantity = 6;
    // $price = $open;
    // $order = $api->buy("BTCUSDT", $quantity, $open);

    // dd($order);

    $api_key = env('BYBIT_KEY');
    $api_secret = env('BYBIT_SECRET');
    $host = ByBit\SDK\ByBitApi::TESTNET_API_URL;

    $bybitApi = new ByBitApi($api_key, $api_secret, $host);

    $params = ["category" => Category::LINEAR, "symbol" => "BTCUSDT"];
    $positions = $bybitApi->positionApi()->getPositionInfo($params);
    dd($positions);


    $params = [
        "category" => "linear",
        "symbol" => "BTCUSDT",
        "takeProfit" => "82250",
        "stopLoss" => "70037.50"
    ];

    $order = $bybitApi->positionApi()->getPositionInfo($params);
    dd($order);

    $coin = 'BTCUSDT';
    $params = ['symbol' => $coin, 'category' => Category::LINEAR];
    $price_data = $bybitApi->marketApi()->getTickers($params);

    $set = $price_data['list'][0]['lastPrice'];
    $price = $set - ($set * 0.2 / 100); //1%
    $take_provit = $price + ($price * 0.5 / 100); //5%
    $stop_loss = $price - ($price * 0.1 / 100); //1%
    $amount = 10;
    $qty = ($amount * doubleval(env('LEVERAGE', 10))) / doubleval($set);

    $orderLinkId = uniqid();

    $params = [
        "category" => Category::LINEAR,
        "symbol" => $coin,
        "side" => Side::BUY,
        "orderType" => OrderType::LIMIT,
        "qty" => strval(round($qty, 3)),
        "price" => strval(round($price, 2)),
        "timeInForce" => TimeInForce::GTC,
        "positionIdx" => PositionIdx::ONE_WAY_MODE,
        "orderLinkId" => $orderLinkId,
        "reduceOnly" => false,
        // "takeProfit" => strval(round($take_provit, 2)),
        // "stopLoss" => strval(round($stop_loss, 2)),
        "tpslMode" => "Full",
    ];

    $order = $bybitApi->tradeApi()->placeOrder($params);

    dd($order);






    $bybit=new BybitV5($api_key,$api_secret, $host);


//     $test = [
//         "category" => "linear",
//         "symbol" => "BTCUSDT",
//         "side" => "Buy",
//         "orderType" => "Limit",
//         "qty" => "1",
//         "price" => "25000",
//         "timeInForce" => "GTC",
//         "positionIdx" => 0,
//         "orderLinkId" => "usdt-test-01",
//         "reduceOnly" => false,
//         "takeProfit" => "28000",
//         "stopLoss" => "20000",
//         "tpslMode" => "Partial",
//         "tpOrderType" => "Limit",
//         "slOrderType" => "Limit",
//         "tpLimitPrice" => "27500",
//         "slLimitPrice" => "20500"
// ];

    $params = [
        "category" => Category::LINEAR,
        "symbol" => $coin,
        "side" => Side::BUY,
        "orderType" => OrderType::LIMIT,
        "qty" => "5",
        "price" => strval(round($price, 2)),
        "timeInForce" => TimeInForce::GTC,
        "positionIdx" => PositionIdx::ONE_WAY_MODE,
        "orderLinkId" => $orderLinkId,
        "reduceOnly" => false,
        "takeProfit" => strval(round($take_provit, 2)),
        "stopLoss" => strval(round($stop_loss, 2)),
        "tpslMode" => "Full",
    ];

    $spot = [
        "category" => "spot",
        "symbol" => $coin,
        "side" => "Buy",
        "orderType" => "Limit",
        "qty" => "5",
        "price" => $price,
        "timeInForce" => "PostOnly",
        "takeProfit" => strval(round($take_provit, 2)),
        "stopLoss" => strval(round($stop_loss, 2)),
        "tpOrderType" => "Market",
        "slOrderType" => "Market"
    ];

    $test = [
        "category" => "linear",
        "symbol" => "BTCUSDT",
        "side" => "Sell",
        "orderType" => "Limit",
        "qty" => "5",
        "price" => $price,
        "timeInForce" => "GTC",
        "positionIdx" => 0,
        "orderLinkId" => $orderLinkId,
        "reduceOnly" => true
    ];

    $test = [

        "category" => "spot",
        "symbol" => $coin,
        "side" => "Buy",
        "orderType" => "Limit",
        "qty" => "10",
        "price" => $price,
        "timeInForce" => "PostOnly",
        "orderLinkId" => "spot-test-01",
        "isLeverage" => 0,
        "orderFilter" => "Order"
    ];

    $bybit=new BybitV5($api_key,$api_secret, $host);

    //You can set special needs
    $bybit->setOptions([
        //Set the request timeout to 60 seconds by default
        'timeout'=>10,

        'headers'=>[
            //X-Referer or Referer - 經紀商用戶專用的頭參數
            //X-BAPI-RECV-WINDOW 默認值為5000
            //cdn-request-id
            'X-BAPI-RECV-WINDOW'=>'6000',
        ]
    ]);

    try {
        $result=$bybit->order()->postCreate([
            'category'=> 'spot',
            'symbol'=> $coin,
            'side'=> 'buy',
            'orderType'=>'limit',
            'qty'=> '5',
            'price'=> $price,
            'orderLinkId'=> $orderLinkId,
        ]);
        dd($result);
    }catch (\Exception $e){
        print_r($e->getMessage());
    }


    $orderLinkId = uniqid();
$params = ["category" => Category::LINEAR, "symbol" => "BTCUSDT", "side" => Side::BUY, "positionIdx" => PositionIdx::ONE_WAY_MODE, "orderType" => OrderType::LIMIT, "qty" => "0.001", "price" => "10000", "timeInForce" => TimeInForce::GTC, "orderLinkId" => $orderLinkId];
$order = $bybitApi->tradeApi()->placeOrder($params);


    try {
        $order = $bybitApi->tradeApi()->placeOrder($test);
    } catch (\Throwable $th) {
        dd($th->getMessage());
        //throw $th;
    }

    dd($order);
    dd($order);

    // Get Position Info
    // $params = ["category" => "linear", "symbol" => "ETHUSDT"];
    // $positions = $bybitApi->positionApi()->getPositionInfo($params);
    // dd($positions);

    // $params = ['accountType' => AccountType::UNIFIED];
    // $result = $bybitApi->accountApi()->getWalletBalance($params);
    // dd($result);

    $params = [
        "category" => "linear",
        "symbol" => "BTCUSDT",
        "side" => "Buy",
        "orderType" => "Limit",
        "qty" => "5",
        "price" => "45862",
        "timeInForce" => "GTC",
        "positionIdx" => 0,
        "orderLinkId" => "usdt-test-01",
        "reduceOnly" => false,
        "takeProfit" => "45962",
        "stopLoss" => "44958",
        "tpslMode" => "Full",
    ];

    // Get order long
    // $params = ["category" => "linear", "symbol" => "ETHUSDT"];
    $positions = $bybitApi->tradeApi()->placeOrder($params);
    dd($positions);

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
        "orderLinkId" => $orderLinkId,
    ];
    $order = $bybitApi->tradeApi()->placeOrder($params);
    dd($order);

    readline('Press enter to continue');

});
