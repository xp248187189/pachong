<?php


/**
 * 爬取 二狗码头博客(www.twodogs.top) 的福利图
 */
class GetBeautyPicture{
	/**
	 * 打印数组
	 * @param  array $arr 数组
	 */
	static public function dump($arr){
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	}
	/**
	 * 自己封装的 cURL 方法
	 * @param $url 请求网址
	 * @param bool $params 请求参数
	 * @param bool $header header头
	 * @param bool $ispost 是否post请求
	 * @param bool $https https协议
	 * @return bool|mixed
	 */
	static public function curl($url, $params = false, $header = false, $ispost = false, $https = false){
	    $httpInfo = array();
	    $ch = curl_init();
	    //强制使用 HTTP/1.1
	    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	    //在HTTP请求中包含一个"User-Agent: "头的字符串。
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36');
	    //在尝试连接时等待的秒数。设置为0，则无限等待。
	    // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	    //允许 cURL 函数执行的最长秒数。
	    // curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
	    if ($header) {
		    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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
	 * 获取图片url
	 * @return array
	 */
	static public function getImgUrl(){
		$header = [
	    		'Accept: application/json, text/javascript, */*; q=0.01',
	    		'Content-Type: application/json'
	    	];
		$imgUrl = [];
		for ($i = 1; $i >0; $i++) {
			$url_1 = 'http://twodogs.top:8020/api/Web/SearchFulieList';
			$params_1 = json_encode(['page'=>$i,'size'=>1]);
			$ispost_1 = true;
			$https_1 = false;
			$ids = self::curl($url_1,$params_1,$header,$ispost_1,$https_1);
			$ids = json_decode($ids,true);
			if (array_key_exists('Data',$ids)) {
				if(!empty($ids['Data'])){
					foreach ($ids['Data'] as $key => $value) {
						$url_2 = 'http://twodogs.top:8020/api/Web/GetFuliDetail/'.$value['Id'];
						$params_2 = [];
						$ispost_2 = false;
						$https_2 = false;
						$res = self::curl($url_2,$params_2,$header,$ispost_2,$https_2);
						$res = json_decode($res,true);
						if (array_key_exists('Data',$res) && array_key_exists('detail',$res['Data'])) {
							foreach ($res['Data']['detail'] as $k => $v) {
								$tem['url'] = $v['Imgurl'];
								$tem['id'] = $value['Id'];
								$imgUrl[] = $tem;
							}
						}
					}
				}else{
					break;
				}
			}
		}
		return $imgUrl;
	}
	/**
	 * 保存图片
	 * @return [type] [description]
	 */
	static public function saveImage($dir = './beautyPicture'){
		if (!is_dir($dir)) {
			mkdir($dir,0777,true);
		}
		$imgUrl = self::getImgUrl();
		foreach ($imgUrl as $key => $value) {
			$temArr = explode('/', $value['url']);
			$saveName = end($temArr);
			if (!file_exists($dir.'/'.$saveName)) {
				$header = [
			    		'Referer: http://www.twodogs.top/fulidetail.html?id='.$value['id']
			    	];
				$params = [];
				$ispost = false;
				$https = false;
				$res = self::curl($value['url'],$params,$header,$ispost,$https);
				$fp = fopen($dir.'/'.$saveName, 'a');
			    fwrite($fp, $res);
			    fclose($fp);
			}
		}
	}
}

GetBeautyPicture::saveImage();
// GetBeautyPicture::dump(GetBeautyPicture::getImgUrl());
