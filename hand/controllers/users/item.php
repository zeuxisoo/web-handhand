<?php
namespace Hand\Controllers\Users;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Models;
use Hand\Helpers\Upload;
use Hand\Helpers\Image;
use Hand\Helpers\Format;
use Hand\Helpers\Paginate;

class Item extends Controller {

    function __construct() {
        parent::__construct();

        $this->app_config        = $this->slim->config('app.config');
        $this->upload_base_root  = $this->app_config['upload']['item']['root'];
        $this->upload_size_root  = [
            '120x120' => $this->upload_base_root.'/120x120',
            '200x200' => $this->upload_base_root.'/200x200',
            '250x250' => $this->upload_base_root.'/250x250',
            '525x525' => $this->upload_base_root.'/525x525'
        ];
        $this->upload_max_images = $this->app_config['default']['item']['upload_max_images'];
    }

    function create() {
        if ($this->slim->request->isPost() === true) {
            $title       = $this->slim->request->post('title');
            $category    = $this->slim->request->post('category');
            $property    = $this->slim->request->post('property');
            $description = $this->slim->request->post('description');
            $price       = $this->slim->request->post('price');
            $delivery    = $this->slim->request->post('delivery');
            $status      = $this->slim->request->post('status');
            $images      = $_FILES['images'];

            $valdiator = Validator::factory($this->slim->request->post());
            $valdiator->add('title', locale('Please enter title'))->rule('required')
                      ->add('category', locale('Please enter category'))->rule('required')
                      ->add('property', locale('Please enter property'))->rule('required')
                      ->add('description', locale('Please enter description'))->rule('required')
                      ->add('price', locale('Please enter price'))->rule('required')
                      ->add('delivery', locale('Please enter delivery'))->rule('required');

            $valid_type    = 'error';
            $valid_message = '';

            if ($valdiator->inValid() === true) {
                $valid_message = $valdiator->firstError();
            }else if (array_key_exists($category, $this->app_config['item']['category']) === false) {
                $valid_message = locale('Category not exists');
            }else if (array_key_exists($property, $this->app_config['item']['property']) === false) {
                $valid_message = locale('Property not exists');
            }else if (array_key_exists($delivery, $this->app_config['item']['delivery']) === false) {
                $valid_message = locale('Delivery not exists');
            }else if (array_key_exists($status, $this->app_config['item']['status']['user']) === false) {
                $valid_message = locale('Status not exists');
            }else if (is_numeric($price) === false) {
                $valid_message = locale('Invalid price format');
            }else{
                $uploaded_infos = Upload::instance([
                    'save_root'    => $this->upload_size_root['525x525'],
                    'allow_format' => ['gif','jpg','jpeg','png'],
                ])->multiUpload($images);

                if (Format::hasError($uploaded_infos) === true) {
                    foreach($uploaded_infos as $upload_info) {
                        @unlink($upload_info['saved_file']['path']);
                    }

                    $valid_message = locale('Please make sure all file formats are image');
                }else{
                    $uploaded_paths = Format::toUploadedPaths($uploaded_infos);

                    Image::instance(['enable_fill' => true])->multiResize($uploaded_paths, 525, 525);
                    Image::instance(['enable_fill' => true, 'save_root' => $this->upload_size_root['120x120']])->multiResize($uploaded_paths, 120, 120);
                    Image::instance(['enable_fill' => true, 'save_root' => $this->upload_size_root['200x200']])->multiResize($uploaded_paths, 200, 200);
                    Image::instance(['enable_fill' => true, 'save_root' => $this->upload_size_root['250x250']])->multiResize($uploaded_paths, 250, 250);

                    $item = Models\Item::create([
                        'title'       => $title,
                        'user_id'     => $_SESSION['user']['id'],
                        'category'    => $category,
                        'property'    => $property,
                        'description' => $description,
                        'price'       => $price,
                        'delivery'    => $delivery,
                        'status'      => $status,
                    ]);

                    foreach($uploaded_paths as $uploaded_path) {
                        Models\ItemImage::create([
                            'user_id' => $_SESSION['user']['id'],
                            'item_id' => $item->id,
                            'image'   => basename($uploaded_path)
                        ]);
                    }

                    $valid_type    = 'success';
                    $valid_message = locale('The new item was created');
                }
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor('user.item.create'));
        }else{
            $this->slim->render('user/item/create.html', [
                'config' => $this->app_config
            ]);
        }
    }

    function manage() {
        $status   = $this->slim->request->get('status', 'hide');
        $total    = Models\Item::whereUserId($_SESSION['user']['id'])->count();
        $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
        $items    = Models\Item::whereStatus($status)->whereUserId($_SESSION['user']['id'])->take(12)->skip($paginate->offset)->with('images')->get(['id', 'title', 'status']);

        $this->slim->render('user/item/manage.html', [
            'items' => $items,
            'paginate' => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

    function delete($item_id) {
        $item = Models\Item::whereUserId($_SESSION['user']['id'])->with('images', 'comments')->find($item_id, ['id', 'status', 'title']);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = locale('Can not found the item');
        }else if (in_array($item->status, ['trade', 'done', 'block']) === true) {
            $valid_message = locale('Can not delete item when item status in trade or done and block');
        }else{
            $item_title  = $item->title;

            foreach($item->images as $item_image) {
                @unlink($this->upload_size_root['120x120'].'/'.$item_image->image);
                @unlink($this->upload_size_root['200x200'].'/'.$item_image->image);
                @unlink($this->upload_size_root['250x250'].'/'.$item_image->image);
                @unlink($this->upload_size_root['525x525'].'/'.$item_image->image);
            }

            $item->trade()->delete();
            $item->bookmarks()->delete();
            $item->comments()->delete();
            $item->images()->delete();
            $item->delete();

            $valid_type    = 'success';
            $valid_message = locale('The item was deleted');
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('user.item.manage').'?'.http_build_query([
            'status' => isset($item->status) === true ? $item->status : ""
        ]));
    }

    function edit_detail($item_id) {
        $item = Models\Item::whereUserId($_SESSION['user']['id'])->find($item_id, [
            'id', 'title', 'category', 'property', 'description', 'price', 'delivery', 'status'
        ]);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = locale('Can not found item');
        }else if (in_array($item->status, ['trade', 'done', 'block']) === true) {
            $valid_message = locale('Can not edit item when item status in trade, done and block');
        }

        if (empty($valid_message) === false) {
            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor('user.item.manage').'?'.http_build_query([
                'status' => isset($item->status) === true ? $item->status : ""
            ]));
            exit;
        }

        if ($this->slim->request->isPost() === true) {
            $title       = $this->slim->request->post('title');
            $category    = $this->slim->request->post('category');
            $property    = $this->slim->request->post('property');
            $description = $this->slim->request->post('description');
            $price       = $this->slim->request->post('price');
            $delivery    = $this->slim->request->post('delivery');
            $status      = $this->slim->request->post('status');

            $valdiator = Validator::factory($this->slim->request->post());
            $valdiator->add('title', locale('Please enter title'))->rule('required')
                      ->add('category', locale('Please enter category'))->rule('required')
                      ->add('property', locale('Please enter property'))->rule('required')
                      ->add('description', locale('Please enter description'))->rule('required')
                      ->add('price', locale('Please enter price'))->rule('required')
                      ->add('delivery', locale('Please enter delivery'))->rule('required');

            if ($valdiator->inValid() === true) {
                $valid_message = $valdiator->firstError();
            }else if (array_key_exists($category, $this->app_config['item']['category']) === false) {
                $valid_message = locale('Category not exists');
            }else if (array_key_exists($property, $this->app_config['item']['property']) === false) {
                $valid_message = locale('Property not exists');
            }else if (array_key_exists($delivery, $this->app_config['item']['delivery']) === false) {
                $valid_message = locale('Delivery not exists');
            }else if (array_key_exists($status, $this->app_config['item']['status']['user']) === false) {
                $valid_message = locale('Status not exists');
            }else if (is_numeric($price) === false) {
                $valid_message = locale('Invalid price format');
            }else{
                $item->update([
                    'title'       => $title,
                    'category'    => $category,
                    'property'    => $property,
                    'description' => $description,
                    'price'       => $price,
                    'delivery'    => $delivery,
                    'status'      => $status,
                ]);

                $valid_type    = 'success';
                $valid_message = locale('The new item was updated');
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor('user.item.edit.detail', ['item_id' => $item_id]));
        }else{
            $this->slim->render('user/item/edit-detail.html', [
                'item'   => $item,
                'config' => $this->app_config,
            ]);
        }
    }

    function edit_image_upload($item_id) {
        $item = Models\Item::whereUserId($_SESSION['user']['id'])->with('images')->find($item_id, ['id']);

        if (empty($item) === true) {
            $this->slim->flash('error', locale('Can not found item'));
            $this->slim->redirect($this->slim->urlFor('user.item.edit.image.upload', ['item_id' => $item_id]));
        }else{
            if ($this->slim->request->isPost() === true) {
                $valid_type    = 'error';
                $valid_message = '';

                if ($item->images->count('id') >= $this->upload_max_images) {
                    $valid_message = locale('Only allow upload %upload_max_images% images in each item', ['upload_max_images' => $this->upload_max_images]);
                }else{
                    $uploaded_infos = Upload::instance([
                        'save_root'    =>  $this->upload_size_root['525x525'],
                        'allow_format' => ['gif','jpg','jpeg','png'],
                    ])->multiUpload($_FILES['images']);

                    if (empty($uploaded_infos[0]) === false) {
                        if ($uploaded_infos[0]['status'] === false) {
                            $valid_message = $uploaded_infos[0]['message'];
                        }else{
                            $uploaded_paths = Format::toUploadedPaths($uploaded_infos);

                            Image::instance(['enable_fill' => true])->multiResize($uploaded_paths, 525, 525);
                            Image::instance(['enable_fill' => true, 'save_root' => $this->upload_size_root['120x120']])->multiResize($uploaded_paths, 120, 120);
                            Image::instance(['enable_fill' => true, 'save_root' => $this->upload_size_root['200x200']])->multiResize($uploaded_paths, 200, 200);
                            Image::instance(['enable_fill' => true, 'save_root' => $this->upload_size_root['250x250']])->multiResize($uploaded_paths, 250, 250);

                            foreach($uploaded_paths as $uploaded_path) {
                                Models\ItemImage::create([
                                    'user_id' => $_SESSION['user']['id'],
                                    'item_id' => $item->id,
                                    'image'   => basename($uploaded_path)
                                ]);
                            }

                            $valid_type    = 'success';
                            $valid_message = locale('New image uploaded');
                        }
                    }
                }

                $this->slim->flash($valid_type, $valid_message);
                $this->slim->redirect($this->slim->urlFor('user.item.edit.image.upload', ['item_id' => $item_id]));
            }else{
                $this->slim->render('user/item/edit-image-upload.html', [
                    'item' => $item
                ]);
            }
        }
    }

    function edit_image_manage($item_id) {
        $item = Models\Item::whereUserId($_SESSION['user']['id'])->with('images')->find($item_id, ['id']);

        if (empty($item) === true) {
            $this->slim->flash('error', locale('Can not found item'));
            $this->slim->redirect($this->slim->urlFor('user.item.manage'));
        }else{
            $this->slim->render('user/item/edit-image-manage.html', [
                'item'        => $item,
                'item_images' => $item->images
            ]);
        }
    }

    function edit_image_delete($item_id, $item_image_id) {
        $item_image = Models\ItemImage::whereUserId($_SESSION['user']['id'])->where('item_id', $item_id)->find($item_image_id, ['id', 'item_id']);

        $valid_type    = 'error';
        $valid_message = '';
        $valid_return  = 'user.item.manage';

        if (empty($item_image) === true) {
            $valid_message = locale('Can not found item image');
        }else{
            @unlink($this->upload_size_root['120x120'].'/'.$item_image->image);
            @unlink($this->upload_size_root['200x200'].'/'.$item_image->image);
            @unlink($this->upload_size_root['250x250'].'/'.$item_image->image);
            @unlink($this->upload_size_root['525x525'].'/'.$item_image->image);

            $item_image->delete();

            $valid_type    = 'success';
            $valid_message = locale('Image deleted');
            $valid_return  = 'user.item.edit.image.manage';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor($valid_return, [
            'item_id' => $item_image->item_id,
        ]));
    }

    function trade_cancel($item_id) {
        $item = Models\Item::whereStatus('trade')->whereUserId($_SESSION['user']['id'])->with('trade')->find($item_id, ['id']);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = locale('Can not found item');
        }else{
            $item->trade()->delete();
            $item->update(['status' => 'hide']);

            $valid_type    = 'success';
            $valid_message = locale('The trade was cancelled');
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('user.item.manage'));
    }

    function trade_done($item_id) {
        $item = Models\Item::whereStatus('trade')->whereUserId($_SESSION['user']['id'])->find($item_id, ['id']);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = locale('Can not found item');
        }else{
            $item->trade()->update(['comment' => locale('Trade was done by seller')]);
            $item->update(['status' => 'done']);

            $valid_type    = 'success';
            $valid_message = locale('The trade was done');
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('user.item.manage').'?status=done');
    }

}
