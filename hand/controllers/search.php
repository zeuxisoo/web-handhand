<?php
namespace Hand\Controllers;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Models;
use Hand\Helpers\Paginate;

class Search extends Controller {

    public function index() {
        if ($this->slim->request->isPost() === true) {
            $keyword  = $this->slim->request->post('keyword');
            $category = $this->slim->request->post('category');

            $valid_type    = 'error';
            $valid_message = '';
            $redirect_to   = $this->slim->urlFor('search.index');

            $validator = Validator::factory($this->slim->request->post());
            $validator->add('keyword', 'Please enter keyword')->rule('required');

            if ($validator->inValid() === true) {
                $valid_message = $validator->firstError();
            }else{
                $valid_type  = 'success';
                $redirect_to = join('', [
                    $this->slim->urlFor('search.result'), '?', http_build_query([
                        'keyword'  => $keyword,
                        'category' => $category
                    ])
                ]);
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($redirect_to);
        }else{
            $this->slim->render('search/index.html');
        }
    }

    public function result() {
        $keyword  = $this->slim->request->get('keyword');
        $category = $this->slim->request->get('category');

        if (empty($keyword) === true) {
            $this->slim->redirect($this->slim->urlFor('search.index'));
        }else{
            $app_config = $this->slim->config('app.config');
            $model_item = Models\Item::whereStatus('publish');
            $model_item = $model_item->where("title", "like", '%'.$keyword.'%');

            $category_name = "ALL";
            if (array_key_exists($category, $app_config['item']['category']) === true) {
                $category_name = $app_config['item']['category'][$category];

                $model_item->where('category', $category);
            }

            $model_item = $model_item->with('user', 'images');
            $model_item = $model_item->orderBy('created_at', 'desc');

            $total    = $model_item->count();
            $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
            $items    = $model_item->take(12)->skip($paginate->offset)->with('images')->get();

            $this->slim->render('search/result.html', [
                'keyword'       => $keyword,
                'category_name' => $category_name,
                'total'         => $total,
                'items'         => $items,
                'paginate'      => $paginate->buildPageBar([
                    'type' => Paginate::TYPE_BACK_NEXT,
                ])
            ]);
        }
    }

}
