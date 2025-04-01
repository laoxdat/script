<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('FILE_ENCRYPTION_BLOCKS', 10000);

$arr_kenh = array(
                  array("K+1 HD","kplus","https://live-drm.fptplay.net/drmlive/k1_720i.stream/manifest.mpd","5e8583c143b939a776d929c050ecd23d","a7e572340288378fa1c27febc1815238"),
                   array("K+PM HD","kpm","https://live-drm.fptplay.net/webplus/nk1pm_1000.stream/manifest.mpd","5346e77b0b2d58c16bfd40d7555de0bd","05ac5dc293153a2a9456f40dc4de2752"),
                  array("K+NS HD","kns","https://live-drm.fptplay.net/drmlive/kns_720i.stream/manifest.mpd","5e8583c143b939a776d929c050ecd23d","a7e572340288378fa1c27febc1815238"),
                  array("KPC HD","kplus","https://live-drm.fptplay.net/drmlive/kpc_720i.stream/manifest.mpd","5e8583c143b939a776d929c050ecd23d","a7e572340288378fa1c27febc1815238"),
                );

#$init_video = "segment_ctvideo_cfm4s_ridp0va0br2159784_cinit_mpd.m4s";
#$init_audio = "segment_ctaudio_cfm4s_ridp0aa0br119836_cinit_mpd.m4s";
$server = "http://123.30.146.172/widevine/";
if(isset($_GET["cid"])){
    $cid=$_GET["cid"];
    foreach ($arr_kenh as $kenh) {
        if($cid == $kenh[1]){
            $ch = $kenh;
            break;
        }
    }
}else{
    $ch = $arr_kenh[1];
}
if(!isset($ch)) exit;

$headers = [
            'accept: */*'
            ,'accept-encoding: gzip, deflate, br'
            ,'accept-language: en,vi;q=0.9,ko;q=0.8'
            ,'referer: https://fptplay.vn/'
            ,'origin: https://fptplay.vn'
            ,'sec-ch-ua: "Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"'
            ,'sec-ch-ua-mobile: ?0'
            ,'sec-fetch-dest: empty'
            ,'sec-fetch-mode: cors'
            ,'sec-fetch-site: cross-site'
            ,'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36'
          ];
$url = $ch[2];
$k = $ch[3];
$kid = $ch[4];
$str1 = get_data($url, $headers);
#header('Content-Type: application/xml; charset=utf-8');
#echo $str;
#exit;
#$str = gzdecode($str);
$str = simplexml_load_string($str1);
#print_r($str);
# download("kpc/1.mpd",$url,$headers);
#exit;
$video = $str->Period->AdaptationSet[0];
$audio = $str->Period->AdaptationSet[1];

//------------------------------------------

$width = $video["width"];
$height = $video["height"];
$par = $video['par'];

$timescale = $video->SegmentTemplate['timescale'];
$video_representation_id = $video->Representation['id'];
$media = $video->SegmentTemplate['media'];
$time_segment = $video->SegmentTemplate->SegmentTimeline->S;
$duration = $time_segment[0]['d']/$timescale;

$url_info = parse_url($url);
$domain = $url_info['scheme'] .'://'. $url_info['host'];
$base_url = dirname($url);

$start_time = $time_segment['t'];
for($i=0;$i<=4;$i++){
    $media_url = $base_url . "/" . str_replace('$Time$', $start_time,str_replace('$RepresentationID$', $video_representation_id,$media));
    $start_time = $start_time + $time_segment[$i]['d'];
    $media_video[$i] = $media_url;
}

$audio_representation_id = $audio->Representation['id'];
$media = $audio->SegmentTemplate['media'];
$time_segment = $audio->SegmentTemplate->SegmentTimeline->S;
$start_time = $time_segment['t'];
for($i=0;$i<=4;$i++){
    $media_url = $base_url . "/" . str_replace('$Time$', $start_time,str_replace('$RepresentationID$', $audio_representation_id,$media));
    $start_time = $start_time + $time_segment[$i]['d'];
    $media_audio[$i] = $media_url;
}
$init_video = "segment_ctvideo_cfm4s_rid".$video_representation_id."_cinit_mpd.m4s";
$init_audio = "segment_ctaudio_cfm4s_rid".$audio_representation_id."_cinit_mpd.m4s";
download($init_video,"https://live-drm.fptplay.net/webplus/nk1pm_1000.stream/".$init_video,$headers);
 download($init_audio,"https://live-drm.fptplay.net/webplus/nk1pm_1000.stream/".$init_audio,$headers);
# $cmd = "cp segment_ctvideo_cfm4s_ridp0va0brp0va0br2203182_cinit_mpd.m4s ".$init_video;
 #   shell_exec($cmd);
  #  $cmd = "cp segment_ctaudio_cfm4s_ridp0aa0brp0aa0br124490_cinit_mpd.m4s ".$init_audio;
#        echo $cmd;
   #     shell_exec($cmd);
#$date=date("Y-m-dH:i:s");
#$str = "#EXTM3U
#EXT-X-TARGETDURATION:4
#EXT-X-VERSION:3
#EXT-X-PROGRAM-DATE-TIME:2021-02-24T13:04:49Z\n";
#//$str = "#EXTM3U";

for($i=0;$i<=4;$i++){
    $f_video = $ch[1] . "_/" . basename(parse_url($media_video[$i], PHP_URL_PATH));
    $f_audio = $ch[1] . "_/" . basename(parse_url($media_audio[$i], PHP_URL_PATH));
    $f_decrypt =  basename(parse_url($media_video[$i], PHP_URL_PATH));
    $fa_decrypt =  basename(parse_url($media_audio[$i], PHP_URL_PATH));
 #   $str = $str . "#EXTINF:"."3.999".",\n";
  #  $str = $str . $server.$f_decrypt."\n";
    if(file_exists($f_video) && file_exists($f_audio)) continue;
    $media_audio[$i] = $media_url;
    download($f_video,$media_video[$i],$headers);
    download($f_audio,$media_audio[$i],$headers);
    shell_exec("cat $init_video $f_video > $f_video.tmp");
    shell_exec("cat $init_audio $f_audio > $f_audio.tmp");

    $cmd = "mp4decrypt --key 1:".$ch[3]." ".$f_video.".tmp ".$f_video.".vi";
    shell_exec($cmd);
    $cmd = "mp4decrypt --key 1:".$ch[3]." ".$f_audio.".tmp ".$f_audio.".au";
    shell_exec($cmd);
    $cmd = "mv ".$f_video.".vi ".$f_decrypt;
    shell_exec($cmd);
      $cmd = "mv ".$f_audio.".au ".$fa_decrypt;
         shell_exec($cmd);
   # $cmd = "/sbin/ffmpeg -i ".$f_video.".vi -i ".$f_audio.".au -c:v copy -c:a copy -f mpegts ".$f_decrypt ." </dev/null >/dev/null 2>/tmp/ffmpeg.log";
   # shell_exec($cmd);
   # $cmd = "/sbin/ffmpeg -i $f_decrypt -map_metadata -1 -c copy ".$f_decrypt.".ts";
   # shell_exec($cmd);

}
sleep (0.1);
#download("kplus/1.mpd",$url,$headers);
#$cmd = "cat kplus/1.mpd";
#$str = shell_exec($cmd);
print_data($str1);
exit;
function print_data($data){
  header("HTTP/1.0 200 OK");
  header("Content-Length: ".strlen($data));
  header("Content-Type: application/dash+xml");
  header("Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS");
  header("Access-Control-Expose-Headers: Server, range, X-Run-Time, Content-Length, Location");
  header('Access-Control-Allow-Origin: *');
  #header("Access-Control-Allow-Headers: x-vsaas-session, x-no-redirect, origin, authorization, x-real-ip, accept, range");
  echo $data;
}

function print_location($m3u8){
        #header('Location: ' .$m3u8 );
        exit;
}

function download($file, $url2,$headers_p) {
        set_time_limit(0);
        $fp = fopen ($file, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_p);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch,CURLOPT_ENCODING , "deflate,gzip,br");
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
}

function post_data($url_p, $data_p, $headers_p){
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_URL, $url_p);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,$data_p);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_p);
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
                }
                curl_close ($ch);
                return $result;
}
function get_data($url2,$headers_p) {
        global $cookies;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_p);
#        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        #curl_setopt($ch, CURLOPT_VERBOSE, 1);
        #curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_ENCODING , "deflate,gzip,br");
 #       curl_setopt($ch, CURLOPT_HEADERFUNCTION, "curlResponseHeaderCallback");
        $result = curl_exec($ch);
        #var_dump($result);
        curl_close($ch);
        return $result;
}

function curlResponseHeaderCallback($ch, $headerLine) {
    global $cookies;
    if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1)
        $cookies[] = $cookie;
    return strlen($headerLine); // Needed by curl
}

 ?>
