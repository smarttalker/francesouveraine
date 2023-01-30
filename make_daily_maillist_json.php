<?php
require_once('./wp-load.php');
require_once ('./sTfunctionsMaillistLibruary.php');

$date = date_create('now')->format('Y-m-d');   //сегодня
$time_range = 86400;// one day 86400
$stop_date = date('Y-m-d', (time() - $time_range));  //вчера

$posts_national = sTgetNewNationalPostsForums($stop_date);  //посты национальные
$posts_regional = sTgetNewRegionalPostsForums($stop_date);  //посты региональные

$dep_ar = sTgetDepartementWithRegions();        //массив департаментов со значением, в каком он регионе
$reg_ar = sTgetRegionsWithArrayDepartements();  //масси регионов, в каждом внутри массив департаментов

$all_new_sujects = sTgetNewSujets($stop_date);  //получение новых суджектов
$all_new_comments = sTgetNewCommentsWithParentPost($stop_date); //получение новых коментов с их родительским суджектом

$result_national_posts = sTresultNationalPosts($posts_national);    //преобразование нацпостов в массив айди постов

global $wpdb;


////        формирование письма national
$national_json = array();

if (!empty($result_national_posts)){
    foreach ($result_national_posts as $post){
        $national_json['forums'][] = sTreturnPostArray($post['post_ID']);
    }
}

if (!empty($all_new_sujects['national'])){
    foreach ($all_new_sujects['national'] as $post){
        $national_json['sujects'][] = sTreturnPostArray($post['post_ID']);
    }
}

if (!empty($all_new_comments['national'])){
    $check_comments_arr = array();
    foreach ($all_new_comments['national'] as $post){
        if (!in_array($post['post_ID'], $check_comments_arr)){
            $check_comments_arr[] = $post['post_ID'];
            $national_json['comments'][] = sTreturnPostArray($post['post_ID']);
        }
    }
}

$query = "INSERT INTO `wp_maillist_json` (`date`, `type`, `region`, `content`) 
        VALUES ('".date( "Ymd", time())."', 'national', '0', '".json_encode($national_json)."');";
if (!empty($national_json)){
    $results = $wpdb->get_results($query);
    echo 'National '.json_encode($national_json).'<br>';
}else{
    echo 'empty national<br>';
}





//////      регионы для отправки начало
$regional_mail_list = array();

$result_regional_posts = sTresultRegionalPosts($posts_regional);
foreach ($result_regional_posts as $key => $post) {
    $regional_mail_list[$key]['posts'] = $post;
}

foreach ($all_new_sujects['regional'] as $key => $suject){
    $regional_mail_list[$key]['sujects'] = $suject;
}

foreach ($all_new_comments['regional'] as $key => $comment){
    $regional_mail_list[$key]['comments'] = $comment;
}
//////      регионы для отправки конец



///////     формирование письма regional
foreach ($regional_mail_list as $key => $val){
    $regional_json = array();
    if (!empty($val['posts'])){
        foreach ($val['posts'] as $post){
            $regional_json['forums'][] = sTreturnPostArray($post['post_ID']);
        }
    }

    if (!empty($val['sujects'])){
        foreach ($val['sujects'] as $post){
            $regional_json['sujects'][] = sTreturnPostArray($post['post_ID']);
        }
    }

    if (!empty($val['comments'] )){
        $check_comments_arr = array();
        foreach ($val['comments'] as $post){
            if (!in_array($post['post_ID'], $check_comments_arr)){
                $check_comments_arr[] = $post['post_ID'];
                $regional_json['comments'][] = sTreturnPostArray($post['post_ID']);
            }
        }
    }

    $query = "INSERT INTO `wp_maillist_json` (`date`, `type`, `region`, `content`) 
        VALUES ('".date( "Ymd", time())."', 'region', '".$key."', '".json_encode($regional_json)."');";
    $results = $wpdb->get_results($query);
    echo 'Region '.$key.' '.json_encode($regional_json).'<br>';

}
if (empty($regional_mail_list))
    echo 'no regioons messages';