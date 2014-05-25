<?php
/*
Copyright (C) 2014  Joni Nieminen

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function GetAuth($username, $pass) {
  $username = htmlentities(urldecode($username));
  $pass = htmlentities(urldecode($pass));
  $url = 'http://api.hitbox.tv/auth/token';
  $data = array('login' => $username, 'pass' => $pass, 'app' => 'Desktop');
  $options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
      ),
  );
  $context  = stream_context_create($options);
  $result = json_decode(file_get_contents($url, false, $context), true);

  return $result['authToken'];
}

function GetJson($username, $pass) {
  $username = htmlentities(urldecode($username));
  $contents = json_decode(file_get_contents('http://www.hitbox.tv/api/media/live/' . $username . '/list?authToken=' . GetAuth($username, $pass) . '&filter=recent&hiddenOnly=false&limit=1&nocache=true&publicOnly=false&yt=false'), true);
  return $contents;
}

function CheckGame($findgame) {
  $gamelist = json_decode(file_get_contents('http://www.hitbox.tv/api/games?q=' . urlencode($findgame) . '&limit=100'), true);
  foreach($gamelist['categories'] as $game) {
    if($game['category_name'] == $findgame) {
      return $game;
    }
  }
}

function UpdateData($username, $pass, $updatetitle, $updategame) {
  $username = htmlentities(urldecode($username));
  $pass = htmlentities(urldecode($pass));
  
  $json = GetJson($username, $pass);
  $title = $json['livestream'][0]['media_status'];
  $game = $json['livestream'][0]['category_name'];
  
  $gamejson = CheckGame($updategame);
  $categoryid = $gamejson['category_id'];
  $categoryname = $gamejson['category_name'];
  $categorynameshort = $gamejson['category_name_short'];
  $categoryseokey = $gamejson['category_seo_key'];
  $categoryviewers = $gamejson['category_viewers'];
  $categorylogosmall = $gamejson['category_logo_small'];
  $categorylogolarge = $gamejson['category_logo_large'];
  $categoryupdated = $gamejson['category_updated'];
  
  $json['livestream'][0]['media_category_id'] = array('category_id' => $categoryid, 'category_name' => $categoryname, 'category_name_short' => $categorynameshort, 'category_seo_key' => $categoryseokey, 'category_viewers' => $categoryviewers, 'category_logo_small' => $categorylogosmall, 'category_logo_large' => $categorylogolarge, 'category_updated' => $categoryupdated);
  
  $json['livestream'][0]['category_id'] = $categoryid;
  $json['livestream'][0]['category_name'] = $categoryname;
  $json['livestream'][0]['category_name_short'] = $categorynameshort;
  $json['livestream'][0]['category_seo_key'] = $categoryseokey;
  $json['livestream'][0]['category_viewers'] = $categoryviewers;
  $json['livestream'][0]['category_logo_small'] = $categorylogosmall;
  $json['livestream'][0]['category_logo_large'] = $categorylogolarge;
  $json['livestream'][0]['category_updated'] = $categoryupdated;
  
  $data = str_replace($title, $updatetitle, json_encode($json));
  $data = str_replace('\/', '/', $data);
  $data = str_replace(array("\r", "\r\n"), "", $data);
  
  $url = 'http://www.hitbox.tv/api/media/live/' . $username . '/list?authToken=' . GetAuth($username, $pass) . '&filter=recent&hiddenOnly=false&limit=1&nocache=true&publicOnly=false&yt=false';
  $chlead = curl_init();
  curl_setopt($chlead, CURLOPT_URL, $url);
  curl_setopt($chlead, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8','Accept-Encoding: gzip,deflate'));
  curl_setopt($chlead, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($chlead, CURLOPT_CUSTOMREQUEST, "PUT"); 
  curl_setopt($chlead, CURLOPT_POSTFIELDS,$data);
  curl_setopt($chlead, CURLOPT_SSL_VERIFYPEER, 0);
  $chleadresult = curl_exec($chlead);
  $chleadapierr = curl_errno($chlead);
  $chleaderrmsg = curl_error($chlead);
  curl_close($chlead);
  return true;
}

?>
