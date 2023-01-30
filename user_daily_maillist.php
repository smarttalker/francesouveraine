<?php
require('./wp-load.php');
require_once ('./sTfunctionsMaillistLibruary.php');


/**
 * делает урл из сохраненного обьекта
 * @param $obj
 * @return string
 */
function MakeUrlPost($obj){
    return '<a href=”'.$obj->title.'” data-ids="'.$obj->id.'">'.$obj->title.'</a>';
}

global $wpdb;
$dep_ar = sTgetDepartementWithRegions();
$date = date('Ymd', time() );
echo $date.'<br>';

$query = "SELECT * FROM `wp_maillist_json` WHERE `date`='{$date}' and `type`='national' AND `region`='0';";
$hational_part = $wpdb->get_results($query);

$national = json_decode($hational_part[0]->content);
echo '<h1>national part</h1>';
if (!empty($national->forums)){
    echo '<h2>forums</h2>';
    foreach ($national->forums as $forum){
        echo MakeUrlPost($forum).'<br>';
    }
}

if (!empty($national->sujects)){
    echo '<h2>sujects</h2>';
    foreach ($national->sujects as $suject){
        echo MakeUrlPost($suject).'<br>';
    }
}

if (!empty($national->comments)){
    echo '<h2>comments</h2>';
    foreach ($national->comments as $comment){
        echo MakeUrlPost($comment).'<br>';
    }
}
echo '<hr>';




$users = sTgetUsersArrByCronMaillistField(10, false);
foreach ($users as $user){
    $fields = get_fields('user_'.$user->ID);
    if (!$fields['user_departement']->term_id){
        echo 'User not department: '. $user->data->user_email.'<br>';
        continue;
    }
    $user_region = $dep_ar[$fields['user_departement']->term_id];

    echo 'User '.$user->ID.' ( '.$user->data->user_email.' ) in '.$dep_ar[$fields['user_departement']->term_id].'<br>';
    $query = "SELECT * FROM `wp_maillist_json` WHERE `date`='{$date}' and `type`='region' AND `region`={$user_region};";
    $region_part = $wpdb->get_results($query);
    $region = json_decode($region_part[0]->content);

    if (!empty($region->forums)){
        echo '<h2>forums</h2>';
        foreach ($region->forums as $forum){
            echo MakeUrlPost($forum).'<br>';
        }
    }

    if (!empty($region->sujects)){
        echo '<h2>sujects</h2>';
        foreach ($region->sujects as $suject){
            echo MakeUrlPost($suject).'<br>';
        }
    }

    if (!empty($region->comments)){
        echo '<h2>comments</h2>';
        foreach ($region->comments as $comment){
            echo MakeUrlPost($comment).'<br>';
        }
    }

echo 'end list';
    echo '<hr>';
}



