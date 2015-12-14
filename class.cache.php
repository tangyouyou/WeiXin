<?php
/*********************/
/*                   */
/*  Version : 5.1.0  */
/*  Author  : 唐悠悠 */
/*  Time    : 151103 */
/*                   */
/*********************/

class cache{

	/**
	 * 设置缓存
	 * @param [type] 缓存名称
	 * @param [type] 缓存数据
	 * @param [type] 缓存时间
	 */
	public function set($name,$data = null,$expire = null){
		$cacheFile = $this->getCacheFile($name);
		//删除缓存
		if(is_null($data)){
			if(is_file($cacheFile)){
				return @unlink($cacheFile);
			}else{
				return true;
			}
		}
		//缓存时间,未传递时间则默认为3600秒
        $expire = sprintf("%0010d", !is_null($expire) ? (int)$expire : 3600);
        //序列化数据
        $data = serialize($data);
        //压缩数据,级别分为1-0,缺省状态下6最佳
        if(function_exists("gzcompress")){
        	$data = gzcompress($data,6);
        }
        $data = "<?php\n//" . $expire . $data . "\n?>";
        $stat = file_put_contents($cacheFile, $data);
        if ($stat) {
            return true;
        } else {;
            return false;
        }
	}

	/**
	 * 获取缓存数据
	 * @param  string $name 缓存名称
	 * @param  null $ctime 过期时间
	 * @return 
	 */
	public function get($name,$ctime = null){
		$cacheFile = $this->getCacheFile($name);
		//缓存文件不存在
        if (!is_file($cacheFile)) {
            return null;
        }
        $content = @file_get_contents($cacheFile);
        if (!$content) {
            return null;
        }
        //文件缓存时间
        $expire = intval(substr($content, 8, 10));
        //文件修改时间
        $mtime = filemtime($cacheFile);
        //缓存失效处理
        if (is_int($ctime) && $mtime + $ctime < time()) {
            @unlink($cacheFile);
            return false;
        }
        if ($expire > 0 && $mtime + $expire < time()) {
            @unlink($cacheFile);
            return false;
        }
  		$data = substr($content, 18, -3);
  		//解压文件
  		if (function_exists("gzuncompress")) {
            $data = gzuncompress($data);
        }
        return unserialize($data);
	}

	/**
	 * 删除缓存
	 * @param  [string] $name [缓存名称]
	 * @return [bool]       [删除状态]
	 */
	public function del($name){
		$cacheFile = $this->getCacheFile($name);
		return is_file($cacheFile) && unlink($cacheFile);
	}



	/**
	 * 获取缓存文件
	 * @param  缓存文件名称
	 * @return [type]       [description]
	 */
	private function getCacheFile($name){
		return  $name;
	}
}