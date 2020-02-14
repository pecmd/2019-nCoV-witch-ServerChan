<?php

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
				//当前省份总的确诊人数
				$s_conf = intval($dat[$i]['confirmedCount']);
				//当前省份总的治愈人数
				$s_cured = intval($dat[$i]['curedCount']);
				//当前省份总的死亡人数
				$s_dead = intval($dat[$i]['deadCount']);	
				
				//如果发现确诊、治愈、死亡任一数据变化就开启推送
				if(intval($s_conf) > intval(get('s_conf')) or intval($s_conf) > intval(get('s_dead')) or intval($s_cured) > intval(get('s_cured')))
				{
					//消息标题ID，这个是为了应对Server酱不能在短时间内发送相同标题的内容
					$id = intval(get("id")) + 1;
					//合成标题
					$title = $id.".".SHENG."确诊".$s_conf."例,增加".(intval($s_conf)-intval(get("s_conf")))."例";
					//保存以上变量到文件
					set('id',$id);
					set('s_conf',$s_conf);
					set('s_cured',$s_conf);
					set('s_dead',$s_conf);
					//合成信息主体内容开始，采用MAKKDOWN语法
					$info = "|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;地区&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;确诊&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;治愈&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;死亡&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|\r\n|:-:|:-:|:-:|:-:|\r\n";
					//遍历所有城市数据开始
					for($j = 0 ;$j < count($dat[$i]['cities']);$j++)
					{		
						//城市名称
						$cityname = $dat[$i]['cities'][$j]['cityName'];
						$info = $info."|".$cityname;
						
						//提取之前存储的数据用于对比（确诊）
						$old = intval(get($cityname.'_conf'));
						$new = intval($dat[$i]['cities'][$j]['confirmedCount']);
						//如果新数据大于旧数据，内容中标注加号(+)变化
						if($new > $old)
						{
							$info = $info."|***".$new.'***&nbsp;&nbsp;+'.($new-$old);
							set($cityname.'_conf',$new);
						}else{
							$info = $info."|***".$new.'***';
						}
						//提取之前存储的数据用于对比（治愈）
						$old = intval(get($cityname.'_cured'));
						$new = intval($dat[$i]['cities'][$j]['curedCount']);
						//如果新数据大于旧数据，内容中标注加号(+)变化
						if($new > $old)
						{
							$info = $info."|***".$new.'***&nbsp;&nbsp;+'.($new-$old);
							set($cityname.'_cured',$new);
						}else{
							$info = $info."|***".$new.'***';
						}
						//提取之前存储的数据用于对比（死亡）
						$old = intval(get($cityname.'_dead'));
						$new = intval($dat[$i]['cities'][$j]['deadCount']);
						//如果新数据大于旧数据，内容中标注加号(+)变化
						if($new > $old)
						{
							$info = $info."|***".$new.'***&nbsp;&nbsp;+'.($new-$old);
							set($cityname.'_dead',$new);
						}else{
							$info = $info."|***".$new.'***';
						}
						//添加信息结尾
						$info = $info."|\r\n";										
					}					
					//进行消息推送
					print_r(sc_send($title, $info, SCKEY));
					
					
				}else{
					echo '<'.SHENG.'>没有新增数据';
				}
				return;
			}
	}
	echo '该省份:<'.SHENG.'>没有数据或输入错误';
}

//本地数据存储函数
function set($key, $value)
{
    $data = @json_decode(file_get_contents('data.json'), true);
    $data[md5($key)] = $value;
    file_put_contents('data.json', json_encode($data));
}
//本地读取函数
function get($key)
{
    $data = @json_decode(file_get_contents('data.json'), true);
    return isset($data[md5($key)]) ? $data[md5($key)] : false;
}
//通过正则抓取网页内容
function get_nCoV_news()
{
    $reg = '#<script\sid="getAreaStat">.*?window.getAreaStat\s=\s(.*?).catch#';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"https://ncov.dxy.cn/ncovh5/view/pneumonia"); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	$data = curl_exec($ch);
	curl_close($ch);
    //进行正则匹配	
    if (preg_match($reg, $data, $out)) {
	//如果匹配成功返回json对象  
        return json_decode($out[1], 1);
    } else {
        echo "正则匹配失败:\r\n" . $data;
    }
    return false;
}
//推送函数
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
