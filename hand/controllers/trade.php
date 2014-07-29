<?php
namespace Hand\Controllers;

use Hand\Abstracts\Controller;
use Hand\Helpers\Paginate;
use Hand\Models;

class Trade extends Controller {

    public function index() {
        $status     = $this->slim->request->get('status', 'trade');
        $item_ids   = Models\ItemTrade::where('user_id', $_SESSION['user']['id'])->lists('item_id');
        $model_item = Models\Item::whereIn('id', $item_ids)->where('status', $status)->with('images');

        $total    = $model_item->count();
        $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
        $items    = $model_item->take(12)->skip($paginate->offset)->get();

        $this->slim->render('trade/index.html', [
            'items'    => $items,
            'paginate' => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

    public function rate($item_id) {
        $item_trade = Models\ItemTrade::where('user_id', $_SESSION['user']['id'])->where('item_id', $item_id)->first();
        $item       = Models\Item::where('status', 'trade')->find($item_id);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item_trade) === true) {
            $valid_message = 'Can not found trade record';
        }else if (empty($item) === true) {
            $valid_message = 'Can not found item';
        }else{
            $this->slim->render('trade/rate.html', [
                'item' => $item,
            ]);
        }

        if (empty($valid_message) === false) {
            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor('trade.index'));
        }
    }

    public function done($item_id) {
        $star       = $this->slim->request->post('star');
        $item_trade = Models\ItemTrade::where('user_id', $_SESSION['user']['id'])->where('item_id', $item_id)->first();
        $item       = Models\Item::where('status', 'trade')->find($item_id);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item_trade) === true) {
            $valid_message = 'Can not found trade record';
        }else if (empty($item) === true) {
            $valid_message = 'Can not found item';
        }else if ($star > 10 || $star < 1) {
            d($star);exit;
            $valid_message = 'The star value incorrect';
        }else{
            $item_trade->update(['star'=> $star]);
            $item->update(['status' => 'done']);

            $valid_type    = 'success';
            $valid_message = 'Thank for you rate. This trade already completed';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('trade.index').'?status='.$item->status);
    }

}
