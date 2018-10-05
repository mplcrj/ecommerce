<?php

use Hcode\Page;
use Hcode\Model\Order;
use Hcode\Model\User;

$app->get('/order/:idorder', function($idorder) {

    User::verifyLogin(false);

    $order = new Order();
    $order->get((int)$idorder);

    $page = new Page();
    $page->setTpl("payment",[
      'order'=>$order->getValues()
    ]);

});

?>
