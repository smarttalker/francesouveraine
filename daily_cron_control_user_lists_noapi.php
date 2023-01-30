<?php
require_once "sTfunctionsLibruary.php";

$users = sTgetUsersArrByCronField(1,false);
//$users = sTgetUsersArrByCronField(100,true);//true это обновление поля крон. фелс без обновления. апи вообще не дергает
foreach ($users as $user){
    $result = sTchangeUserSpecialListNewsletterNoApi($user->data->user_email);
    echo  $result;
}

