<?php

use Hcode\Page;
use Hcode\Model\User;

$app->get('/profile', function() {

  User::verifyLogin(false);

  $user = User::getFromSession();

  $page = new Page();

  $page->setTpl("profile",[
    'user'=>$user->getValues(),
    'profileMsg'=>User::getSuccess(),
    'profileError'=>User::getError()
  ]);

});

$app->post("/profile",function (){

    User::verifyLogin(false);

    if (!isset($_POST['desperson']) || $_POST['desperson'] === ''){
      User::setError("Preencha o seu nome.");
      header("Location: /profile");
      exit;
    }

    if (!isset($_POST['desemail']) || $_POST['desemail'] === ''){
      User::setError("Preencha o seu e-mail.");
      header("Location: /profile");
      exit;
    }

    $user = User::getFromSession();

    if ($_POST['desemail'] !== $user->getdesemail()){

      if(User::checkLoginExist($_POST['desemail']) === true){
        User::setError("Este endereço de e-mail já está cadastrado");
        header("Location: /profile");
        exit;
      }

    }

    $_POST['iduser'] = $user->getiduser();
    $_POST['inadmin'] = $user->getinadmin();
    $_POST['despassword'] = $user->getdespassword();
    $_POST['deslogin'] = $_POST['desemail'];

    $user->setData($_POST);

    $user->update();

    $_SESSION[User::SESSION] = $user->getValues();

    User::setSuccess("Dados alterados com sucesso.");

    header('Location: /profile');
    exit;

});

$app->get('/profile/change-password', function() {

  User::verifyLogin(false);  

  $page = new Page();

  $page->setTpl("profile-change-password",[
      'changePassError'=>User::getError(),
      'changePassSuccess'=>User::getSuccess()
  ]);

});


$app->post('/profile/change-password', function() {

  User::verifyLogin(false);  
  
  if(!isset($_POST['current_pass']) || $_POST['current_pass'] === ''){
      
      User::setError("Digite a senha atual.");
      header("Location: /profile/change-password");
      exit;
      
  }
  
  if(!isset($_POST['new_pass']) || $_POST['new_pass'] === ''){
      
      User::setError("Digite a nova senha.");
      header("Location: /profile/change-password");
      exit;
      
  }
  
  if(!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === ''){
      
      User::setError("Confirme a nova senha.");
      header("Location: /profile/change-password");
      exit;
      
  }
  
  if ($_POST['current_pass'] == $_POST['new_pass']){
      
      User::setError("Sua nova senha deve ser diferente da atual.");
      header("Location: /profile/change-password");
      exit;
      
  }
  
  $user = User::getFromSession();
  
  if(!password_verify($_POST['current_pass'], $user->getdespassword())){
      
     User::setError("Sua senha está inválida.");
      header("Location: /profile/change-password");
      exit;       
      
  }
  
  $user->setdespassword($_POST['new_pass']);
  
  $user->update();
  
  User::setSuccess("Senha alterada com sucesso");
  
  header("Location: /profile/change-password");
  exit; 
  
});

?>

