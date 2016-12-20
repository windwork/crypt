<?php
/**
 * Windwork
 * 
 * 一个开源的PHP轻量级高效Web开发框架
 * 
 * @copyright   Copyright (c) 2008-2016 Windwork Team. (http://www.windwork.org)
 * @license     http://opensource.org/licenses/MIT	MIT License
 */
namespace wf\crypt\adapter;

use \wf\crypt\Exception;

/**
 * 基于xxtea加密算法实现
 * 
 * 算法作者：
 * - David J. Wheeler
 * - Roger M. Needham
 * 
 * 参考源码：
 * Ma Bingyao <mabingyao@gmail.com> (https://github.com/xxtea/xxtea-php)
 *
 * @package     wf.crypt.adapter
 * @author      erzh <cmpan@qq.com>
 * @link        http://www.windwork.org/manual/wf.crypt.html
 * @since       0.1.0
 */
class Xxtea implements \wf\crypt\ICrypt {
	/**
	 * (non-PHPdoc)
	 * @see \wf\crypt\ICrypt::encrypt()
	 */
	public function encrypt($str, $key) {
		if ($str == '') {
			return '';
		}
		
		if (!$key || !is_string($key)) {
			throw new Exception('[wf\\crypt\\Xxtea::encrypt] param 2 ($key) is required.');
		}
		
		$v = $this->str2long($str, true);
		$k = $this->str2long($key, false);
		if (count($k) < 4) {
			for ($i = count($k); $i < 4; $i++) {
				$k[$i] = 0;
			}
		}
		$n = count($v) - 1;
		
		$z = $v[$n];
		$y = $v[0];
		$delta = 0x9E3779B9;
		$q = floor(6 + 52 / ($n + 1));
		$sum = 0;
		while (0 < $q--) {
			$sum = $this->int32($sum + $delta);
			$e = $sum >> 2 & 3;
			for ($p = 0; $p < $n; $p++) {
				$y = $v[$p + 1];
				$mx = $this->int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ $this->int32(
					($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
				$z = $v[$p] = $this->int32($v[$p] + $mx);
			}
			$y = $v[0];
			$mx = $this->int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ $this->int32(
				($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$z = $v[$n] = $this->int32($v[$n] + $mx);
		}
		return $this->long2str($v, false);
	}
	/**
	 * (non-PHPdoc)
	 * @see \wf\crypt\ICrypt::decrypt()
	 */
	public function decrypt($str, $key) {
		if ($str == '') {
			return '';
		}
		
		if (!$key || !is_string($key)) {
			throw new Exception('[wf\\crypt\\Xxtea::decrypt] param 2 ($key) is required.');
		}
		$v = $this->str2long($str, false);
		$k = $this->str2long($key, false);
		if (count($k) < 4) {
			for ($i = count($k); $i < 4; $i++) {
				$k[$i] = 0;
			}
		}
		$n = count($v) - 1;
		
		$z = $v[$n];
		$y = $v[0];
		$delta = 0x9E3779B9;
		$q = floor(6 + 52 / ($n + 1));
		$sum = $this->int32($q * $delta);
		while ($sum != 0) {
			$e = $sum >> 2 & 3;
			for ($p = $n; $p > 0; $p--) {
				$z = $v[$p - 1];
				$mx = $this->int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ $this->int32(
					($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
				$y = $v[$p] = $this->int32($v[$p] - $mx);
			}
			$z = $v[$n];
			$mx = $this->int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ $this->int32(
				($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$y = $v[0] = $this->int32($v[0] - $mx);
			$sum = $this->int32($sum - $delta);
		}
		return $this->long2str($v, true);
	}

	/**
	 * 长整型转换为字符串
	 *
	 * @param long $v
	 * @param boolean $w
	 * @return string
	 */
	private function long2str($v, $w) {
		$len = count($v);
		$n = $len << 2;
		if ($w) {
			$m = $v[$len - 1];
			$n -= 4;
			if (($m < $n - 3) || ($m > $n)) {
				return false;
			}
			$n = $m;
		}
		
		$s = [];
		for ($i = 0; $i < $len; $i++) {
			$s[$i] = pack("V", $v[$i]);
		}
		
		if ($w) {
			return substr(join('', $s), 0, $n);
		} else {
			return join('', $s);
		}
	}

	/**
	 * 字符串转化为长整型
	 *
	 * @param string $s
	 * @param boolean $w
	 * @return Ambigous <multitype:, number>
	 */
	private function str2long($s, $w) {
		$v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
		$v = array_values($v);
		
		if ($w) {
			$v[count($v)] = strlen($s);
		}
		
		return $v;
	}

	/**
	 * @param int $n
	 * @return number
	 */
	private function int32($n) {
		return ($n & 0xffffffff);
	}
}

