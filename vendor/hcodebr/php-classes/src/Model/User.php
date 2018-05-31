<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;
use Rain\Tpl\Exception;

class User extends Model {

    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";

    public static function login($login,$password){
        $sql = new Sql();

        $results = $sql->select("select * from tb_users where deslogin = :login", array(
           ":login"=>$login
        ));

        if (count($results) === 0){
            throw new Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        if(password_verify($password,$data["despassword"]) === true){
            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;

        }else throw new Exception("Usuário inexistente ou senha inválida.");

    }

    public static function verifyLogin($inadmin = true){

        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0
            ||
            (bool)$_SESSION[User::SESSION]["inadmin"]!== $inadmin
        ){
            header("Location: /admin/login");

            exit;
        }

    }

    public static function logout(){

        $_SESSION[User::SESSION]= NULL;

    }

    public static function listAll(){

    $sql = new Sql();

    return $sql->select("select * from tb_users a inner join tb_persons b using(idperson) order by b.desperson");
    }

    public function get($iduser){

        $sql = new Sql();

        $results = $sql->select("select * from tb_users a inner join tb_persons b using(idperson) where a.iduser = :iduser",array(
            "iduser"=>$iduser
        ));

        $this->setData($results[0]);

    }

    public function save(){

        $sql = new Sql();

        $results = $sql->select("call sp_users_save(:desperson,:deslogin,:despassword,:desemail,:nrphone,:inadmin)",array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    public function update(){

        $sql = new Sql();

        $results = $sql->select("call sp_usersupdate_save(:iduser,:desperson,:deslogin,:despassword,:desemail,:nrphone,:inadmin)",array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    public function delete(){

        $sql = new Sql();

        $sql->query("call sp_users_delete(:iduser)",array(
            ":iduser"=>$this->getiduser()
        ));

    }

    public static function getForgot($email){

        $sql = new Sql();

        $results = $sql->select("select * from tb_persons a inner join tb_users b using(idperson) where a.desemail = :email", array(
            ":email"=>$email
        ));

        if (count($results)=== 0){
            throw new \Exception("Não foi possível recuperar a senha");
        }else{

            $data = $results[0];

            $results2 = $sql->select("call sp_userspasswordsrecoveries_create(:iduser,:desip)",array(
               ":iduser"=>$data["iduser"],
                "desip"=>$_SERVER["REMOTE_ADDR"]
            ));

            if (count($results2) === 0){
                throw new \Exception("Não foi possível recuperar a senha");
            }else{

                $dataRecovery = $results2[0];

                $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
                $code = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', User::SECRET, 0, $iv);
                $result = base64_encode($iv.$code);

                $link = "http://www.hdcocommece.com.br/admin/forgot/reset?code=$code";

                $mailer = new Mailer($data["desemail"],$data["desperson"],"Redefinir Senha da Hcode Store","forgot",array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));

                $mailer->send();

                return $data;

            }

        }

    }

}

?>