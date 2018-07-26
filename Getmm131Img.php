<?php

/**
 * 获取 MM131美女图片(http://www.mm131.com)
 */
class Getmm131Img{
	/**
	 * 打印数组
	 * @param  array $arr 数组
	 */
	public static function dump($arr){
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	}
	/**
	 * 自己封装的 cURL 方法
	 * @param $url 请求网址
	 * @param bool $params 请求参数
	 * @param bool $ispost 是否post请求
	 * @param bool $https https协议
	 * @return bool|mixed
	 */
	public static function curl($url){
	    $httpInfo = array();
	    $ch = curl_init();
	    //强制使用 HTTP/1.1
	    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	    //在HTTP请求中包含一个"User-Agent: "头的字符串。
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36');
	    //在尝试连接时等待的秒数。设置为0，则无限等待。
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	    //允许 cURL 函数执行的最长秒数。
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    //TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    //设置url
	    curl_setopt($ch, CURLOPT_URL, $url);
	    $header = [
	    	'Referer:http://www.mm131.com',
	    	'Content-Type: image/jpeg'
	    ];
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	    $response = curl_exec($ch);

	    if ($response === FALSE) {
	        return false;
	    }
	    //最后一个收到的HTTP代码
	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    //获取一个cURL连接资源句柄的信息(包括 最后一个收到的HTTP代码)
	    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
	    curl_close($ch);
	    //这里就直接返回接收到的数据，不反悔http信息了
	    if ($httpCode == 200) {
	    	return $response;
	    }
	    return $httpCode;
	}
	/**
	 * 保存图片
	 */
	public static function saveImage($dir = './getmm131Img'){
		if (!is_dir($dir)) {
			mkdir($dir,0777,true);
		}
		//固定id范围为 1-9999，一个id为一篇文章
		for ($i=1; $i <9999 ; $i++) { 
			//判断每一个id是否有第一页，第一页都没有表示此id是空的，直接跳过
			if (is_numeric(self::curl('http://img1.mm131.me/pic/'.$i.'/1.jpg'))) {
				continue;
			}
			//每一页一张图片，固定页数为 1-99 把每一篇文章的的每一张图片找到
			for ($j=1; $j <100 ; $j++) { 
				$url = 'http://img1.mm131.me/pic/'.$i.'/'.$j.'.jpg';
				$res = self::curl($url);
				//判断是否有图片，循环到某一页，没有图片了，那么直接跳出
				if (!is_numeric($res)) {
					$fp = fopen($dir.'/'.$i.'_'.$j.'.jpg', 'a');
					fwrite($fp, $res);
					fclose($fp);
				}else{
					break;
				}
			}
		}
	}
}

Getmm131Img::saveImage();
