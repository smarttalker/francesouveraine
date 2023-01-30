<?php
require('./wp-load.php');

/**
 * общая функция выполнения запроса cUrl
 * @param $url
 * @param $method
 * @param array $data_string
 * @return bool|string
 */
function sTsubscribePlugCurl($url, $method, $data_string = array()){
    $ch = curl_init($url);
    if ($method == 'PUT'){
        $data_string = json_encode($data_string);
        curl_setopt($ch, CURLOPT_POST, 1);
    }

    if ($method == 'POST')
        curl_setopt($ch, CURLOPT_POST, true);
    else
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method == 'POST'){
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    }else
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                //'Content-Length: ' . strlen(json_encode($data_string))
            )
        );

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


/**
 * сохранение число в файл
 * @param $number
 * @param string $filename
 */
function sTsaveNumberInFile($number, $filename = 'last_users_update.cng'){
    $fp = fopen($filename, "w+");
    fwrite($fp, $number . PHP_EOL);
    fclose($fp);
}


/**
 * получение число из файла
 * @param string $filename
 * @return false|string
 */
function sTgetNumberFromFile($filename = 'last_users_update.cng'){
    $fp = fopen($filename, "a+");
    $content = fread($fp, filesize($filename));
    fclose($fp);
    return $content;
}


/**
 * получение списков подписок
 * @return mixed
 */
function sTgetLists(){
    global $client_key;
    global $client_secret;
    $url = get_bloginfo('url')."/wp-json/newsletter/v2/lists?client_key={$client_key}&client_secret={$client_secret}";
    $result =  sTsubscribePlugCurl($url, 'GET');
    return json_decode($result);
}


/**
 * получение списка подписчиков
 * @param int $count
 * @param int $page
 * @return bool|string
 */
function sTgetSubscribersList($count = 10, $page = 1){
    global $client_key;
    global $client_secret;
    $url = get_bloginfo('url')."/wp-json/newsletter/v2/subscribers?client_key={$client_key}&client_secret={$client_secret}&per_page={$count}&page={$page}";
    return sTsubscribePlugCurl($url, 'GET');
}


/**
 * получение подписчика по ID или по мейл
 * @param $email
 * @return bool|string
 */
function sTgetSubscriber($email){
    global $client_key;
    global $client_secret;
    $url = get_bloginfo('url')."/wp-json/newsletter/v2/subscribers/{$email}?client_key={$client_key}&client_secret={$client_secret}";
    return sTsubscribePlugCurl($url, 'GET');
}


/**
 * удаление из ньюслетер по ID или по мейлу
 * @param $email
 * @return bool|string
 */
function sTdeleteSubscriber($email){
    global $client_key;
    global $client_secret;
    $url = get_bloginfo('url')."/wp-json/newsletter/v2/subscribers/{$email}?client_key={$client_key}&client_secret={$client_secret}";
    return sTsubscribePlugCurl($url, 'DELETE');
}


/**
 * добавление юзера в ньюслетер
 * @param $array_data
 * @return bool|string
 */
function sTaddSubscriber($array_data){
    global $client_key;
    global $client_secret;
    $url = get_bloginfo('url')."/wp-json/newsletter/v2/subscribers?client_key={$client_key}&client_secret={$client_secret}";
    return sTsubscribePlugCurl($url, 'POST', $array_data);
}


/**
 * обновление указанных полей
 * @param $email
 * @param $data_string
 * @return bool|string
 */
function sTapdateSubscriberWithEmail($email, $data_string){
    global $client_key;
    global $client_secret;
    echo $email . ' roles changed:<br>';
    var_dump($data_string);
    $url = get_bloginfo('url')."/wp-json/newsletter/v2/subscribers/{$email}?client_key={$client_key}&client_secret={$client_secret}";
    return sTsubscribePlugCurl($url, 'PUT',$data_string);
}


/**
 * возвращает имя роли от слага
 * @param $slug_role
 * @return mixed|string
 */
function sTgetNameRoleFromSlug($slug_role){
    return $slug_role ? wp_roles()->get_names()[ $slug_role ] : '';
}


/**
 * получение списка ролей с ВП
 * @return array
 */
function sTgetWpRoles(){
    require_once ABSPATH . 'wp-admin/includes/user.php';
    $all_roles = get_editable_roles();
    $result_arr = array();
    foreach($all_roles as $role){
        $result_arr[] = $role['name'];
    }
    return $result_arr;
}


/**
 * получение массива для первого прохода. точку останова в файл
 * @param int $number
 * @return mixed
 */
function sTgetUsersArrByIdAndFile($number = 3){ //тут можно ограничить загрузку юзеров
    $offset = sTgetNumberFromFile();
    if ($offset == '')
        $offset = 0;

    $users = get_users([
        'orderby'=> 'ID',
        'order'=>'ASC',
        'offset'=>$offset,
        'number'=>$number
    ]);
    $count = count($users);
    echo 'selected '. $count . ' users<br>';
    sTsaveNumberInFile($offset + $count);
    return $users;
}


/**
 * получение массива юзеров по полю крон и сразу меняет на текущее
 * @return mixed
 */
function sTgetUsersArrByCronField($number, $update = true){
    $arr =  array(
        'orderby'=> 'ID',
        'order'=>'ASC',
        'meta_query'        => array(
            array(
                'key'       => 'cron_time',
                'value'     => date( "Ymd", time()),
                'type'      => 'numeric',
                'compare'   => '!='
            )
        )
    );
    if ($number)
        $arr['number'] = $number;
    $users = get_users($arr);
    if ($update){
        foreach ($users as $user){
            update_field('cron_time',date( "Ymd", time()),'user_'.$user->ID);
        }
    }

    return $users;
}


/**
 * получение юзеров с нулевым полем cron_time, теоретически это новые пользователи
 * @return mixed
 */
function sTgetUsersArrByEmptyCronField($number = 100){
    $array = array(
        'orderby'=> 'ID',
        'order'=>'ASC',
        'meta_query'        => array(
            array(
                'key'       => 'cron_time',
                'value'     => 0,
                'type'      => 'numeric',
                'compare'   => '='
            )
        )
    );
    if($number != 0)
        $array['number'] = $number;
    $users = get_users();
    foreach ($users as $user){
        update_field('cron_time',date( "Ymd", time()),'user_'.$user->ID);
    }
    return $users;
}


/**
 * получение массива пользователей за сутки по полю cron_message_time
 * @return mixed
 */
function sTgetUsersArrByCronEmailField($time_range = 86400){
    $needed_time = time() - $time_range;// one day 86400,  полтора дня 129600
    $users = get_users([
        'orderby'=> 'ID',
        'order'=>'ASC',
        'meta_query'        => array(
            'relation' => 'OR', // default relation
            array(
                'key'       => 'cron_message_time',
                'value'     => $needed_time,
                'type'      => 'numeric',
                'compare'   => '<='
            ),
            array(
                'key'       => 'cron_message_time',
                'value'     => '',
                'type'      => 'numeric',
                'compare'   => '='
            )
        )
    ]);
    foreach ($users as $user){
        update_field('cron_message_time',date( "Ymd", time()),'user_'.$user->ID);
    }
    return $users;
}


/**
 * @param $users
 *  перенос юзеров в плагин ньюслеттер
 */
function sTaddUsersInNewsletter($users){
    foreach ($users as $user){
        $fields = get_fields('user_'.$user->ID);
        $user_arr = ['id'=>$user->ID,
            'role'=>$user->roles,
            'email'=>$user->data->user_email,
            'name'=>$user->data->display_name
        ];
        $result = json_decode(sTgetSubscriber($user_arr['email']));
        if ($result->code == -1){
            $array_data = array(
                "email"=> $user_arr['email'],
                "first_name"=> $user_arr['name'],
                "country"=> "FR",
                "region"=> $fields['user_departement']->name,
                "status" => "confirmed",
            );

            if(sTaddSubscriber($array_data)){//после полного добавления надо изменить
                echo $user_arr['email'] . ' was added<br>';
                // sTchangeUserListNewsletter($user_arr['email']);
                sTchangeUserSpecialListNewsletter($user_arr['email']);
                //после внесения юзера в ньюзлетер, обновить поля крона
                update_field('cron_message_time',date( "Ymd", time()),'user_'.$user->ID);
                update_field('cron_time',date( "Ymd", time()),'user_'.$user->ID);
            }
        }
    }
}


/**
 * @param $email
 * функция изменения ролей юзера в списке ньюслетер
 */
function sTchangeUserListNewsletter($email){
    $user = get_user_by('email', $email);
    $list_array = array();  //массив для ролей. соответствие в ифе ниже.

    $list_from_newsletter = sTgetLists();
    foreach ($user->roles as $role){
        foreach ($list_from_newsletter as $list_role){
            if ($list_role->name == sTgetNameRoleFromSlug($role)){
                $list_array[] = array("id"=> $list_role->id,
                    "value"=> 1);
            }
        }
    }
    $tmp_arr = array(
        "lists" =>$list_array
    );
    sTapdateSubscriberWithEmail($email, $tmp_arr);
}


/**
 * функция меняющая роли подписки в зависимости от оплаты. условия прописаны вручную внутри
 * @param $email
 */
function sTchangeUserSpecialListNewsletter($email){
    $user = get_user_by('email', $email);
    $list_array = array();  //массив для ролей. соответствие в ифе ниже.
    //$list_from_newsletter = sTgetLists();
    //$user_role = null;
    //$deleted_role = null;
//------------------------------------------
    if(in_array('wpfs_no_access',$user->roles) && !in_array('wpfs_bronze',$user->roles)
        && !in_array('wpfs_silver',$user->roles) && !in_array('wpfs_basic',$user->roles)
        && !in_array('wpfs_gold',$user->roles)) {
        $user_role = 8;
    }elseif(in_array('wpfs_gold',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 10;
        $deleted_role = 11;
    }elseif (in_array('wpfs_gold',$user->roles) && in_array('wpfs_no_access',$user->roles)){
        $user_role = 11;
        $deleted_role = 10;
    }elseif(in_array('wpfs_bronze',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 4;
        $deleted_role = 5;
    }elseif (in_array('wpfs_bronze',$user->roles) && in_array('wpfs_no_access',$user->roles)){
        $user_role = 5;
        $deleted_role = 4;
    }elseif(in_array('wpfs_silver',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 6;
        $deleted_role = 7;
    }elseif(in_array('wpfs_silver',$user->roles) && in_array('wpfs_no_access',$user->roles)) {
        $user_role = 7;
        $deleted_role = 6;
    }elseif(in_array('wpfs_basic',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 2;
        $deleted_role = 3;
    }elseif(in_array('wpfs_basic',$user->roles) && in_array('wpfs_no_access',$user->roles)) {
        $user_role = 3;
        $deleted_role = 2;
    } else {
        $user_role = 9;
    }
//------------------------------------------
    if ($user_role){
        $list_array[] = array("id"=> $user_role, "value"=> 1);
    }
    if ($deleted_role){
        $list_array[] = array("id"=> $deleted_role, "value"=> 0);
    }

    $tmp_arr = array(
        "lists" =>$list_array
    );
    return sTapdateSubscriberWithEmail($email, $tmp_arr);
}


/**
 * очищает все поля списков ньюзлетер
 * @param $email
 */
function sTclearUserListNewsletter($email){
    $list_array = array();  //массив для ролей. соответствие в ифе ниже.
    $list_from_newsletter = sTgetLists();
    foreach ($list_from_newsletter as $list_role){
        $list_array[] = array("id"=> $list_role->id,
            "value"=> 0);
    }
    sTapdateSubscriberWithEmail($email, array("lists" =>$list_array));
}


/**
 * функция меняющая роли подписки в зависимости от оплаты. условия прописаны вручную внутри
 * @param $email
 */
function sTchangeUserSpecialListNewsletterNoApi($email, $update = false){
    global $wpdb;
    $user = get_user_by('email', $email);
    $list_array = array();  //массив для ролей. соответствие в ифе ниже.
    // $list_from_newsletter = sTgetLists();
    //$user_role = null;
    //$deleted_role = null;
//------------------------------------------
    if(in_array('wpfs_no_access',$user->roles) && !in_array('wpfs_bronze',$user->roles)
        && !in_array('wpfs_silver',$user->roles) && !in_array('wpfs_basic',$user->roles)
        && !in_array('wpfs_gold',$user->roles)) {
        $user_role = 8;
    }elseif(in_array('wpfs_gold',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 10;
        $deleted_role = 11;
    }elseif (in_array('wpfs_gold',$user->roles) && in_array('wpfs_no_access',$user->roles)){
        $user_role = 11;
        $deleted_role = 10;
    }elseif(in_array('wpfs_bronze',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 4;
        $deleted_role = 5;
    }elseif (in_array('wpfs_bronze',$user->roles) && in_array('wpfs_no_access',$user->roles)){
        $user_role = 5;
        $deleted_role = 4;
    }elseif(in_array('wpfs_silver',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 6;
        $deleted_role = 7;
    }elseif(in_array('wpfs_silver',$user->roles) && in_array('wpfs_no_access',$user->roles)) {
        $user_role = 7;
        $deleted_role = 6;
    }elseif(in_array('wpfs_basic',$user->roles) && !in_array('wpfs_no_access',$user->roles)) {
        $user_role = 2;
        $deleted_role = 3;
    }elseif(in_array('wpfs_basic',$user->roles) && in_array('wpfs_no_access',$user->roles)) {
        $user_role = 3;
        $deleted_role = 2;
    } else {
        $user_role = 9;
    }
//------------------------------------------
    if ($user_role){
        $list_array[] = array("id"=> $user_role, "value"=> 1);
    }
    if ($deleted_role){
        $list_array[] = array("id"=> $deleted_role, "value"=> 0);
    }

    $tmp_arr = array(
        "lists" =>$list_array
    );

    $fields = array();
    foreach ($tmp_arr['lists'] as $key => $list){
        $fields[] ="`list_{$list['id']}` = {$list['value']}";
    }
    $str = implode(', ', $fields);

    $sql ="UPDATE `{$wpdb->prefix}newsletter` SET ".$str." WHERE `{$wpdb->prefix}newsletter`.`email`='{$email}';";
    $results = $wpdb->get_results($sql);
    if ($update)
        update_field('cron_time',date( "Ymd", time()),'user_'.$user->ID);
    return  $email.' was updated '.$str.'<br>';
}



///////////
/// from send mail list subscriber 23 01
///////////
/**
 * получить регионы
 * @return array
 */
function sTgetRegions(){
    $regions_array = [];
    $regions = get_terms([
        'taxonomy' => "regions",
        'hide_empty' => false,
    ]);
    foreach ($regions as $region){
        $regions_array[$region->term_id] = array();
    }
    return$regions_array;
}


/**
 * получить массив регионов с массивами департаментов в них
 * @return array
 */
function sTgetRegionsWithArrayDepartements(){
    $regions_array = sTgetRegions();
    $departements = get_terms([
        'taxonomy' => "departements",
        'hide_empty' => false,
    ]);
    foreach ($departements as $departement){
        $departament_fields = get_fields('term_'.$departement->term_id);
        $region = $departament_fields['region']->term_id;
        $regions_array[$region][] = $departement->term_id;
    }
    return $regions_array;
}

/**
 * получить массив департаментов с указанием какой регион в конкретном департаменте
 * @return array
 */
function sTgetDepartementWithRegions(){
    $regions_array = sTgetRegions();
    $departements = get_terms([
        'taxonomy' => "departements",
        'hide_empty' => false,
    ]);
    $result_array = array();
    foreach ($departements as $departement){
        $departament_fields = get_fields('term_'.$departement->term_id);
        $result_array[$departement->term_id] = $departament_fields['region']->term_id;
    }
    return $result_array;
}


/**
 * получение массива национальных форумов диапазона времени
 * @param $date
 * @return mixed
 */
function sTgetNewNationalPostsForums($date){
    $posts = get_posts( array(
        'orderby'     => 'date',
        'order'       => 'DESC',
        'meta_key'    => 'national_yes',
        'meta_value'  =>true,
        'post_type'   => 'forums',
        'date_query' => array(
            array(
                'after' => $date,
                'inclusive' => true,
            ),
        ),
    ) );
    return $posts;
}


/**
 * получение массива региональных форумов диапазона времени
 * @param $date
 * @return mixed
 */
function sTgetNewRegionalPostsForums($date){
    $posts = get_posts( array(
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_type'   => 'forums',
        'date_query' => array(
            array(
                'after' => $date,
                'inclusive' => true,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => 'region',
                'compare' => 'EXISTS'
            ),
        )
    ) );
    return $posts;
}


/**
 * получение массива юзеров по указанному региону
 * @param $dep_id
 * @param null $regions_arr
 * @return mixed
 */
function sTgetUsersFromRegion($dep_id, $regions_arr = null){
    if (!$regions_arr)
        $regions_arr = sTgetRegionsWithArrayDepartements();
    $users = get_users(array(   'meta_query' => array(
            array(
                'key' => 'user_departement',
                'value' => $regions_arr[$dep_id],
                'compare' => 'IN',
                //  'type' => 'NUMERIC',
            ),
        )
        )
    );
    return $users;
}


/**
 * получение массива айди коментариев с их родительскими постами
 * @param $date
 * @return array
 */
function sTgetNewCommentsWithParentPost($date){
    $result_arr = array();
    $args =  array(
        'orderby'=> 'comment_date',
        'order'=>'DESC',
        'date_query' => array(
            array(
                'after' => $date,
                'inclusive' => true,
            ),
        ),
    );
    if( $comments = get_comments( $args ) ){
        foreach( $comments as $comment ){
            $cur_post = get_post($comment->comment_post_ID);    //родительское сообщение
            $parent_post = get_fields( $cur_post->ID);
            $item = array(
                'comment_ID'=>$comment->comment_ID,
                // 'post_ID'=>$parent_post['comite']->ID
                'post_ID'=> $cur_post->ID
            );

            if (!$parent_post['comite'])
                continue;
            $parent_fields = get_fields($parent_post['comite']->ID);

            if ($parent_fields['national_yes'] == true){
                if (is_null($result_arr['national']) || !in_array($item, $result_arr['national']))
                    $result_arr['national'][] = $item;
            }else{
                if (is_null($result_arr['regional'][$parent_fields['region']->term_id]) || !in_array($item, $result_arr['regional'][$parent_fields['region']->term_id]))
                    $result_arr['regional'][$parent_fields['region']->term_id][] = $item;
            }
        }
    }
    return $result_arr;
}


/**
 * получение массива постов региональных сортированных по регионам
 * @param $posts_regional
 * @return array
 */
function sTresultRegionalPosts($posts_regional){
    $result_arr = array();
    foreach ($posts_regional as $cur_post){
        $post_fields = get_fields( $cur_post->ID );
        $result_arr[$post_fields['region']->term_id][] = array(
            'post_ID'=>$cur_post->ID
        );
    }
    return $result_arr;
}


/**
 * @param $posts_national
 * @return array
 */
function sTresultNationalPosts($posts_national){
    $result_arr = array();
    foreach ($posts_national as $cur_post){
        $result_arr[] = array(
            'post_ID'=>$cur_post->ID
        );
    }
    return $result_arr;
}


/**
 * @param $stop_date
 * @return array
 */
function sTgetNewSujets($stop_date){
    $result_arr = array();
    $my_posts = get_posts( array(
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_type'   => 'sujets',
        'date_query' => array(
            array(
                'after' => $stop_date,
                'inclusive' => true,
            ),
        ),
    ) );
    foreach ($my_posts as $cur_post){
        $post_fields = get_fields( $cur_post->ID);
        $item = array(
            'post_ID'=>$cur_post->ID
        );

        if (!$post_fields['comite']){
            if (!in_array($item, $result_arr['all']))
                $result_arr['all'][] = $item;
        }else{
            $parent_fields = get_fields($post_fields['comite']->ID);
            if ($parent_fields['national_yes'] == true){
                if (is_null($result_arr['national']) || !in_array($item, $result_arr['national'])){
                    $result_arr['national'][] = $item;
                }
            }else{
                if (is_null($result_arr['regional'][$parent_fields['region']->term_id]) || !in_array($item, $result_arr['regional'][$parent_fields['region']->term_id])){
                    $result_arr['regional'][$parent_fields['region']->term_id][] = $item;
                }
            }
        }
    }
    return $result_arr;
}


/**
 * @param $mail
 * @param $message
 */
function sTsendMail($mail, $message){
    $subject = "Заголовок письма";
    $message = $message.' </br>';
    $headers  = "Content-type: text/html; charset=UTF-8 \r\n";
    $headers .= "From: От кого письмо <from@example.com>\r\n";
    $headers .= "Reply-To: reply-to@example.com\r\n";

    mail($mail, $subject, $message, $headers);
}




function here(){
    echo '<h1>here!</h1>';
}