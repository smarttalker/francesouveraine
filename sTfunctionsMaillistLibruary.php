<?php
require_once('./wp-load.php');


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
        //echo '<h2>suject '.$cur_post->ID.'</h2>';
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


/**
 * получение массива юзеров по полю крон и сразу меняет на текущее
 * @return mixed
 */
function sTgetUsersArrByCronMaillistField($number, $update = true){
    $arr =  array(
        'orderby'=> 'ID',
        'order'=>'ASC',
        'meta_query'        => array(
            'relation' => 'OR',
            array(
                'key'       => 'cron_daily_maillist',
                'value'     => date( "Ymd", time()),
                'type'      => 'numeric',
                'compare'   => '!='
            ),
            array(
                'key' => 'cron_daily_maillist',
                'compare' => 'NOT EXISTS'
            )
        )
    );
    if ($number)
        $arr['number'] = $number;
    $users = get_users($arr);
    if ($update){
        foreach ($users as $user){
            update_field('cron_daily_maillist',date( "Ymd", time()),'user_'.$user->ID);
        }
    }

    return $users;
}


/**
 * возвращает титл линьк и айди по айди
 * @param $id
 * @return array
 */
function sTreturnPostArray($id){
    return array('id' => $id,
        'title' => get_the_title($id),
        'link' =>get_the_permalink($id)
    );
}


function showArray($array){
    echo '<pre>';
    var_dump($array);
    echo '</pre>';
}
