
<?php
define('FILE_ENCRYPTION_BLOCKS', 10000);

$arr_kenh = array(
                  array("K+1 HD","kplus","https://live-drm.fptplay.net/drmlive/k1_720i.stream/manifest.mpd","5e8583c143b939a776d929c050ecd23d","a7e572340288378fa1c27febc1815238"),
                  array("K+PM HD","kpm","https://live-drm.fptplay.net/webplus/nk1pm_1000.stream/manifest.mpd","5346e77b0b2d58c16bfd40d7555de0bd","05ac5dc293153a2a9456f40dc4de2752"),
                  array("K+NS HD","kns","https://live-drm.fptplay.net/drmlive/kns_720i.stream/manifest.mpd","b214bf5e0743b48481b4b851340d0efb","75719fc23c983bd592368af5865e41f7"),
                  array("KPC HD","kpc","https://live-drm.fptplay.net/drmlive/kpc_720i.stream/manifest.mpd","8700b0731a46777f59e524948ce37071","9ab06b9395ae3d98b59c1f4bf34aa423"),
                );

$init_video = "segment_ctvideo_cfm4s_ridp0va0br2159784_cinit_mpd.m4s";
$init_audio = "segment_ctaudio_cfm4s_ridp0aa0br119836_cinit_mpd.m4s";
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
//echo $url;
$str = get_data($url, $headers);
#echo $str . "\n";
$str = simplexml_load_string($str);
$video = $str->Period->AdaptationSet[0];
$audio = $str->Period->AdaptationSet[1];
$time = $str->UTCTiming['value'];
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
for($i=0;$i<4;$i++){
    $media_url = $base_url . "/" . str_replace('$Time$', $start_time,str_replace('$RepresentationID$', $video_representation_id,$media));
    $start_time = $start_time + $time_segment[$i]['d'];
    $media_video[$i] = $media_url;
}

$audio_representation_id = $audio->Representation['id'];
$media = $audio->SegmentTemplate['media'];
$time_segment = $audio->SegmentTemplate->SegmentTimeline->S;
$start_time = $time_segment['t'];
for($i=0;$i<4;$i++){
    $media_url = $base_url . "/" . str_replace('$Time$', $start_time,str_replace('$RepresentationID$', $audio_representation_id,$media));
    $start_time = $start_time + $time_segment[$i]['d'];
    $media_audio[$i] = $media_url;
}
$init_video = "segment_ctvideo_cfm4s_rid".$video_representation_id."_cinit_mpd.m4s";
$init_audio = "segment_ctaudio_cfm4s_rid".$audio_representation_id."_cinit_mpd.m4s";
download($init_video,"https://live-drm.fptplay.net/webplus/nk1pm_1000.stream/".$init_video,$headers);
 download($init_audio,"https://live-drm.fptplay.net/webplus/nk1pm_1000.stream/".$init_audio,$headers);
$sequense = "sequense_".$ch[1];
$tmp1 = file_get_contents($sequense);
if(!isset($tmp1)){
        $seq=0;
}
else{
        $pieces = explode(" ", $tmp1);
        $seq = $pieces[0];
        $old = $pieces[1];
}
$seq = (int) $seq;
$date=date("Y-m-dH:i:s");

$str = "#EXTM3U
#EXT-X-TARGETDURATION:4
#EXT-X-VERSION:3
#EXT-X-PROGRAM-DATE-TIME:$time\n";

for($i=0;$i<4;$i++){
    $f_video = $ch[1] . "_/" . basename(parse_url($media_video[$i], PHP_URL_PATH));
    $f_audio = $ch[1] . "_/" . basename(parse_url($media_audio[$i], PHP_URL_PATH));
    $f_decrypt = $ch[1] . "/" . basename(parse_url($media_video[$i], PHP_URL_PATH));    
    $tmp2 = $seq. " " . $f_decrypt;
    if($i==0){
            if($tmp1 != $tmp2){
                $seq ++;
                shell_exec("rm -f $old");
                file_put_contents($sequense, "$seq $f_decrypt");
            }
            $str = $str . "#EXT-X-MEDIA-SEQUENCE:$seq\n";
    }
    $ccc = $seq + $i;
    $f_decrypt = $ch[1] . "/" .$ccc . ".ts";
    if(!file_exists($f_decrypt)) {
            $media_audio[$i] = $media_url;
            download($f_video,$media_video[$i],$headers);
            download($f_audio,$media_audio[$i],$headers);

            shell_exec("cat $init_video $f_video > $f_video.tmp");
            shell_exec("cat $init_audio $f_audio > $f_audio.tmp");

            $cmd = "mp4decrypt --key 1:".$ch[3]." ".$f_video.".tmp ".$f_video.".vi";
            shell_exec($cmd);
            $cmd = "mp4decrypt --key 1:".$ch[3]." ".$f_audio.".tmp ".$f_audio.".au";
            shell_exec($cmd);
            $cmd = "/usr/bin/MP4Box -add $f_audio.au $f_video.vi 1>/dev/null 2>&1";
            shell_exec($cmd);
            $cmd = "ffmpeg -i ".$f_video.".vi -async 1 -vsync 1 -c copy -bsf:v h264_mp4toannexb -f mpegts -y $f_decrypt 1>/dev/null 2>&1";
            shell_exec($cmd);
            #$cmd = "mp42ts $f_video.vi $f_decrypt";
            #shell_exec($cmd);
    }
    #$cmd = "ffprobe -v quiet -of csv=p=0 -show_entries format=duration $f_decrypt";
    #$time1 = shell_exec($cmd);
    #$time1 = trim($time1);
    $str = $str . "#EXTINF:3.99,\n";
    $str = $str . $f_decrypt."\n";
    #$cmd = "rm -rf ".$ch[1] . "_/*";
    #shell_exec($cmd);
}
print_data($str);
exit;
function print_data($data){
  header("HTTP/1.0 200 OK");
  header("Content-Length: ".strlen($data));
  header("Content-Type: application/vnd.apple.mpegurl");
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        #curl_setopt($ch, CURLOPT_VERBOSE, 1);
        #curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_ENCODING , "deflate,gzip,br");
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, "curlResponseHeaderCallback");
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
