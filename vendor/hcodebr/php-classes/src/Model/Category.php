<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Mailer;
use Hcode\Model;
use Rain\Tpl\Exception;

class Category extends Model {

    public static function listAll(){

    $sql = new Sql();

    return $sql->select("select * from tb_categories order by descategory");

    }

}

?>