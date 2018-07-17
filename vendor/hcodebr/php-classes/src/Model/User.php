<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;
use Rain\Tpl\Exception;

class User extends Model {

    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";

    public static function getFromSession(){

        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){

            $user = new User();

            $user->set(($_SESSION[User::SESSION]));

            return $user;

        }

    }

    public static function checkLogin($inadmin = true){

        if(
            !isset($_SESSION[User::SESSION]) //SESSÃO NÃO DEFINIDA
            ||
            !$_SESSION[User::SESSION] //SESSÃO DEFINIDA PORÉM VAZIA
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0 // SESSÃO DEFINIDA PORÉM IDUSER < 0
        ){
            return false; //NÃO ESTÁ LOGADO
        }else{

            if ($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true){

                return false;

            }elseif ($inadmin === false) {

                return true;

            }else{

                return false;

            }

        }

    }

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

        if (!User::checkLogin($inadmin)){

            if($inadmin){
              header("Location: /admin/login");
            }else header("Location: /login");

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

    public static function validForgotDecrypt($code)
    {
        $code = base64_decode($code);
        $result = mb_substr($code, openssl_cipher_iv_length('aes-256-cbc'), null, '8bit');
        $iv = mb_substr($code, 0, openssl_cipher_iv_length('aes-256-cbc'), '8bit');;
        $idrecovery = openssl_decrypt($result, 'aes-256-cbc', User::SECRET, 0, $iv);
        $sql = new Sql();
        $results = $sql->select("
         SELECT *
         FROM tb_userspasswordsrecoveries a
         INNER JOIN tb_users b USING(iduser)
         INNER JOIN tb_persons c USING(idperson)
         WHERE
         a.idrecovery = :idrecovery
         AND
         a.dtrecovery IS NULL
         AND
         DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
     ", array(
            ":idrecovery"=>$idrecovery
        ));
        if (count($results) === 0)
        {
            throw new \Exception("Não foi possível recuperar a senha.");
        }
        else
        {
            return $results[0];
        }
    }

    public static function setForgotUsed($idrecovery){

        $sql = new Sql();

        $sql->query("update tb_userspasswordsrecoveries set dt_recovery = now() where idrecovery = :idrecovery", array(
            ":idrecovery=>$idrecovery"
        ));

    }

    public function setPassword($password){

        $sql = new Sql();

        $sql->query("update tb_users set despassword = :password where iduser = :iduser", array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));

    }

    public static function setError($msg){

        $_SESSION[User::ERROR] = $msg;

    }

    public static function getError(){

        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : "";

        User::clearError();

        return $msg;

    }

    public static function clearError(){

        $_SESSION[User::ERROR] = NULL;

    }

}

?>
