<?php

/**
 * 获取bing近15天的每日一图
 */
class GetBingEverydayImg{
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
	public static function curl($url, $params = false, $ispost = false, $https = false){
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
	    if ($https) {
	        //FALSE 禁止 cURL 验证对等证书（peer's certificate）。要验证的交换证书可以在 CURLOPT_CAINFO 选项中设置，或在 CURLOPT_CAPATH中设置证书目录。
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	        //设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。
	        //译者注：公用名(Common Name)一般来讲就是填写你将要申请SSL证书的域名 (domain)或子域名(sub domain)。
	        // 设置成 2，会检查公用名是否存在，并且是否与提供的主机名匹配。
	        // 0 为不检查名称。
	        // 在生产环境中，这个值应该是 2（默认值）。
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	    }
	    if ($ispost) {
	        //TRUE 时会发送 POST 请求，类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。
	        curl_setopt($ch, CURLOPT_POST, true);
	        //这个参数可以是 urlencoded 后的字符串，类似'para1=val1&para2=val2&...'，也可以使用一个以字段名为键值，字段数据为值的数组。
	        //如果value是一个数组，Content-Type头将会被设置成multipart/form-data
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	        //需要获取的 URL 地址，也可以在curl_init() 初始化会话的时候。
	        curl_setopt($ch, CURLOPT_URL, $url);
	    } else {
	        if ($params) {
	            if (is_array($params)) {
	                $params = http_build_query($params);
	            }
	            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
	        } else {
	            curl_setopt($ch, CURLOPT_URL, $url);
	        }
	    }

	    $response = curl_exec($ch);

	    if ($response === FALSE) {
	        //echo "cURL Error: " . curl_error($ch);
	        return false;
	    }
	    //最后一个收到的HTTP代码
	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    //获取一个cURL连接资源句柄的信息(包括 最后一个收到的HTTP代码)
	    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
	    curl_close($ch);
	    //这里就直接返回接收到的数据，不反悔http信息了
	    return $response;
	}
	/**
	 * 获取图片地址
	 * @return array
	 */
	public static function getImg(){
		$img = [];
		for ($i=0; $i <=7 ; $i++) { 
			$url = 'https://www.bing.com/HPImageArchive.aspx?format=js&idx='.$i.'&n=8';
			$res = self::curl($url,false,false,true);
			$res = json_decode($res,true);
			foreach ($res['images'] as $key => $value) {
				unset($res['images'][$key]['hs']);
				$img[] = $res['images'][$key];
			}
		}
		return $img;
	}
	/**
	 * 保存图片
	 * @return [type] [description]
	 */
	static public function saveImage($dir = './getBingEverydayImg'){
		if (!is_dir($dir)) {
			mkdir($dir,0777,true);
		}
		$img = self::getImg();
		foreach ($img as $key => $value) {
			// $saveName = $value['enddate'];
			$temArr = explode('.',$value['url']);
			$saveName = $value['enddate'].'.'.$temArr[1];
			if (!file_exists($dir.'/'.$saveName)) {
				$url = 'https://cn.bing.com'.$value['url'];
				$params = [];
				$ispost = false;
				$https = true;
				$res = self::curl($url,$params,$ispost,$https);
				$fp = fopen($dir.'/'.$saveName, 'a');
				fwrite($fp, $res);
				fclose($fp);
			}
		}
	}
}

// GetBingEverydayImg::dump(GetBingEverydayImg::getImg());
GetBingEverydayImg::saveImage();
