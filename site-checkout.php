<?php

use Hcode\Page;
use Hcode\Model\Address;
use Hcode\Model\Cart;
use Hcode\Model\User;

$app->get('/checkout', function() {

  User::verifyLogin(false);

  $address = new Address();
  $cart = Cart::getFromSession();
  
  if (isset($_GET['zipcode']))$_GET['zipcode'] = $cart->getdeszipcode();

  if (isset($_GET['zipcode'])){

      $address->loadFromCEP($_GET['zipcode']);

      $cart->setdeszipcode($_GET['zipcode']);

      $cart->save();

      $cart->getCalculateTotal();

  }
  
  if (!$address->getdesaddress()) $address->setdesaddress('');
  if (!$address->getdesnumber()) $address->setdesnumber('');
  if (!$address->getdescomplement()) $address->setdescomplement('');
  if (!$address->getdesdistrict()) $address->setdesdistrict('');
  if (!$address->getdescity()) $address->setdescity('');
  if (!$address->getdesstate()) $address->setdesstate('');
  if (!$address->getdescountry()) $address->setdescountry('');
  if (!$address->getdeszipcode()) $address->setdeszipcode('');

  $page = new Page();
  $page->setTpl("checkout",[
    'cart'=>$cart->getValues(),
    'address'=>$address->getValues(),
    'products'=>$cart->getProducts(),
    'error'=>Address::getMsgError()
  ]);

});

$app->post('/checkout', function(){

  User::verifyLogin(false);  

  if (!isset($_POST['zipcode']) || $_POST['zipcode'] === ''){
    Address::setMsgError("Informe o CEP.");
    header("Location: /checkout");
    exit;
  } 

  if (!isset($_POST['desaddress']) || $_POST['desaddress'] === ''){
    Address::setMsgError("Informe o endereço.");
    header("Location: /checkout");
    exit;
  }

  if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === ''){
    Address::setMsgError("Informe o bairro.");
    header("Location: /checkout");
    exit;
  }

  if (!isset($_POST['descity']) || $_POST['descity'] === ''){
    Address::setMsgError("Informe a cidade.");
    header("Location: /checkout");
    exit;
  }


  if (!isset($_POST['desstate']) || $_POST['desstate'] === ''){
    Address::setMsgError("Informe o estado.");
    header("Location: /checkout");
    exit;
  }

  if (!isset($_POST['descountry']) || $_POST['descountry'] === ''){
    Address::setMsgError("Informe o país.");
    header("Location: /checkout");
    exit;
  }

  $address = new Address();

  $_POST['deszipcode'] = $_POST['zipcode'];
  $_POST['idperson'] = $user->getidperson();

  $address->setData($_POST);

  $address->save();

  header("Location: /order");
  exit;

});

?>