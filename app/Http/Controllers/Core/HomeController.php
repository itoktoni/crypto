<?php

namespace App\Http\Controllers\Core;

use Alkhachatryan\LaravelWebConsole\LaravelWebConsole;
use App\Dao\Traits\RedirectAuth;
use App\Http\Controllers\Controller;
use ByBit\SDK\ByBitApi;
use ByBit\SDK\Enums\Category;
use ByBit\SDK\Enums\OrderType;
use ByBit\SDK\Enums\PositionIdx;
use ByBit\SDK\Enums\Side;
use ByBit\SDK\Enums\TimeInForce;

class HomeController extends Controller
{
    use RedirectAuth;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (auth()->check()) {
            return redirect()->route('login');
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $bybitApi = new ByBitApi('x4323swz3a1Z6wf9i1', '7JvkL92nD4DC2ySSTmOEovJiqOhdPjFXhoFV', ByBitApi::TESTNET_API_URL);

        $coin = 'HYPEUSDT';
        $params = ['symbol' => $coin, 'category' => 'linear'];
        $price_data = $bybitApi->marketApi()->getTickers($params);

        $set = $price_data['list'][0]['lastPrice'];
        $price = $set - ($set * 0.2 / 100); //1%
        $take_provit = $price + ($price * 0.5 / 100); //5%
        $stop_loss = $price - ($price * 0.1 / 100); //1%

        $orderLinkId = uniqid();

        $params = [
            "category" => Category::LINEAR,
            "symbol" => $coin,
            "side" => Side::BUY,
            "orderType" => OrderType::LIMIT,
            "qty" => "1",
            "price" => strval(round($price, 2)),
            "timeInForce" => TimeInForce::GTC,
            "positionIdx" => PositionIdx::ONE_WAY_MODE,
            "orderLinkId" => $orderLinkId,
            "reduceOnly" => false,
            "takeProfit" => strval(round($take_provit, 2)),
            "stopLoss" => strval(round($stop_loss, 2)),
            "tpslMode" => "Full",
        ];

        $order = $bybitApi->tradeApi()->placeOrder($params);
        dd($order);

        if (empty(auth()->user())) {
            header('Location: ' . route('public'));
        }

        return view('pages.home.dashboard', [
            'chart' => [],
        ]);
    }

    public function delete($code)
    {
        $navigation = session()->get('navigation');
        if (!empty($navigation) && array_key_exists($code, $navigation)) {
            unset($navigation[$code]);
            session()->put('navigation', $navigation);
        }

        return redirect()->back();
    }

    public function console()
    {
        return LaravelWebConsole::show();
    }

    public function doc()
    {
        return view('doc');
    }

    public function error402()
    {
        return view('errors.402');
    }
}
