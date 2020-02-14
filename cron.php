<?php

/* 
前提：

※ 你需要一个带有PHP环境的服务器或者电脑(24小时开机)
※ 设置一个计划任务，定时运行这个脚本(如：php /home/cron.php),推荐10分钟运行一次，间隔尽量大于10分钟

其次：

1.先申请一个GITHUB账号
2.进入https://sc.ftqq.com,使用刚才注册的账号进行登录，获取SCKEY填到下面，并且修改你需要关注的省份
3.按网站提示扫码绑定微信即可接收信息推送 

由于之前的API中转接口访问人数过多，所以干脆直接访问丁香园原始网址，丁香园疫情网址为：https://ncov.dxy.cn/ncovh5/view/pneumonia

*/

//以下字段需要自己定义
define("SCKEY", "你的SCKEY");
define("SHENG","辽宁省");

@date_default_timezone_set('Asia/Chongqing');

find_pro(get_nCoV_news());

function find_pro($dat)
{
	for($i = 0 ;$i < count($dat);$i++)
	{
		if($dat[$i]['provinceName'] == SHENG)
			{
				$s_conf = intval($dat[$i]['confirmedCount']);
				$s_cured = intval($dat[$i]['curedCount']);
				$s_dead = intval($dat[$i]['deadCount']);	
				
				if(intval($s_conf) > intval(get('s_conf')) or intval($s_conf) > intval(get('s_dead')) or intval($s_cured) > intval(get('s_cured')))
				{
					$id = intval(get("id")) + 1;
					
					$title = $id.".".SHENG."确诊".$s_conf."例,增加".(intval($s_conf)-intval(get("s_conf")))."例";
					
					set('id',$id);
					set('s_conf',$s_conf);
					set('s_cured',$s_conf);
					set('s_dead',$s_conf);
					
					$info = "|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;地区&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;确诊&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;治愈&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;死亡&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|\r\n|:-:|:-:|:-:|:-:|\r\n";
					for($j = 0 ;$j < count($dat[$i]['cities']);$j++)
					{	
						$cityname = $dat[$i]['cities'][$j]['cityName'];
						$info = $info."|".$cityname;
						
						$old = intval(get($cityname.'_conf'));
						$new = intval($dat[$i]['cities'][$j]['confirmedCount']);
						if($new > $old)
						{
							$info = $info."|***".$new.'***&nbsp;&nbsp;+'.($new-$old);
							set($cityname.'_conf',$new);
						}else{
							$info = $info."|***".$new.'***';
						}

						$old = intval(get($cityname.'_cured'));
						$new = intval($dat[$i]['cities'][$j]['curedCount']);
						if($new > $old)
						{
							$info = $info."|***".$new.'***&nbsp;&nbsp;+'.($new-$old);
							set($cityname.'_cured',$new);
						}else{
							$info = $info."|***".$new.'***';
						}
						
						$old = intval(get($cityname.'_dead'));
						$new = intval($dat[$i]['cities'][$j]['deadCount']);
						if($new > $old)
						{
							$info = $info."|***".$new.'***&nbsp;&nbsp;+'.($new-$old);
							set($cityname.'_dead',$new);
						}else{
							$info = $info."|***".$new.'***';
						}
						
						$info = $info."|\r\n";										
					}					
					
					print_r(sc_send($title, $info, SCKEY));
					
					
				}else{
					echo '<'.SHENG.'>没有新增数据';
				}
				return;
			}
	}
	echo '该省份:<'.SHENG.'>没有数据或输入错误';
}

function set($key, $value)
{
    $data = @json_decode(file_get_contents('data.json'), true);
    $data[md5($key)] = $value;
    file_put_contents('data.json', json_encode($data));
}

function get($key)
{
    $data = @json_decode(file_get_contents('data.json'), true);
    return isset($data[md5($key)]) ? $data[md5($key)] : false;
}

function get_nCoV_news()
{
    $reg = '#<script\sid="getAreaStat">.*?window.getAreaStat\s=\s(.*?).catch#';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://ncov.dxy.cn/ncovh5/view/pneumonia"); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	$data = curl_exec($ch);
	curl_close($ch);
	
    if (preg_match($reg, $data, $out)) {
        return json_decode($out[1], 1);
    } else {
        echo "正则匹配失败:\r\n" . $data;
    }
    return false;
}

function sc_send($text, $desp = '', $key = '')
{
    $postdata = http_build_query(
        array(
        'text' => $text,
        'desp' => $desp
    )
    );

    $opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    )
);
    $context  = stream_context_create($opts);
    return $result = file_get_contents('https://sc.ftqq.com/'.$key.'.send', false, $context);
}
?>