<?php
require_once('./wp-load.php');
require_once "secret_keys.php";
require_once "work_newsletter_users.php";
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
$mail_string = '<h3>Hello, friend!</h3>';
$national_json = array();
if (!empty($result_national_posts) || !empty($all_new_sujects['national']) || !empty($all_new_comments['national']))
    $mail_string .= ' <h4>email national</h4>';

if (!empty($result_national_posts)){
    $mail_string .= '<strong>new forum posts</strong><br>';
    foreach ($result_national_posts as $post){
        $mail_string .= get_the_permalink($post['post_ID']).'<br>';
        $national_json['forums'][] = get_the_permalink($post['post_ID']);
    }
}

if (!empty($all_new_sujects['national'])){
    $mail_string .= '<strong>new sujects</strong><br>';
    foreach ($all_new_sujects['national'] as $post){
        $mail_string .= get_the_permalink($post['post_ID']).'<br>';
        $national_json['sujects'][] = get_the_permalink($post['post_ID']);
    }
}

if (!empty($all_new_comments['national'])){
    $mail_string .= '<strong>new comments</strong><br>';
    $check_comments_arr = array();
    foreach ($all_new_comments['national'] as $post){
        if (!in_array($post['post_ID'], $check_comments_arr)){
            $mail_string .= get_the_permalink($post['post_ID']).'<br>';
            $check_comments_arr[] = $post['post_ID'];
            $national_json['comments'][] = get_the_permalink($post['post_ID']);
        }
    }
}

$query = "INSERT INTO `wp_maillist_json` (`date`, `type`, `region`, `content`) 
        VALUES ('".date( "Ymd", time())."', 'national', '0', '".json_encode($national_json)."');";
//$results = $wpdb->get_results($query);
/*
 * date( "Ymd", time())
 * INSERT INTO `wp_maillist_json` (`date`, `type`, `region`, `content`) VALUES ('20230121', 'national', '0', 'svrverververvbervb');
// $sql ="UPDATE `{$wpdb->prefix}newsletter` SET ".$str." WHERE `{$wpdb->prefix}newsletter`.`email`='{$params['email']}';";
$results = $wpdb->get_results($sql);
 * */



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
    $mail_regional = '<h4> email region '.$key.'</h4>';

    $regional_json = array();
    if (!empty($val['posts'])){
        $mail_regional .= '<strong>new forum posts</strong><br>';
        foreach ($val['posts'] as $post){
            //echo $post['post_ID'].'<hr>';
            $mail_regional .= get_the_permalink($post['post_ID']).'<br>';
            $regional_json['forums'][] = get_the_permalink($post['post_ID']);
        }
    }

    if (!empty($val['sujects'])){
        $mail_regional .= '<strong>new sujects</strong><br>';
        foreach ($val['sujects'] as $post){
            //echo $post['post_ID'].'<hr>';
            $mail_regional .= get_the_permalink($post['post_ID']).'<br>';
            $regional_json['sujects'][] = get_the_permalink($post['post_ID']);
        }
    }

    if (!empty($val['comments'] )){
        $mail_regional .= '<strong>new comments</strong><br>';
        $check_comments_arr = array();
        foreach ($val['comments'] as $post){
            //echo $post['comment_ID'].' '.$post['post_ID'].'<hr>';
            if (!in_array($post['post_ID'], $check_comments_arr)){
                $mail_regional .= get_the_permalink($post['post_ID']).'<br>';
                $check_comments_arr[] = $post['post_ID'];
                $regional_json['comments'][] = get_the_permalink($post['post_ID']);
            }
        }
    }

    $query = "INSERT INTO `wp_maillist_json` (`date`, `type`, `region`, `content`) 
        VALUES ('".date( "Ymd", time())."', 'region', '".$key."', '".json_encode($regional_json)."');";
//    $results = $wpdb->get_results($query);

    echo $mail_string.$mail_regional.'<hr>';
 /*   $users = sTgetUsersFromRegion($key); //этим отправлять следующее
    $users_emails = array();
    foreach ($users as $user){
        $users_emails[] = $user->data->user_email;
        //sTsendMail //ут отправлять письмо
    }*/
   // var_dump($users_emails);
}
