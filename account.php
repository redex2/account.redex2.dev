<?php
	require_once(__DIR__."/session.php");
	class ACCOUNT
	{
		private $session=null;
		private $unchar="0123456789abc";
		private $unchar_len=null;
		private $unlen=6;//user name length
		public $base32="ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
		private $base32_len=null;
		private $un_chars_org=[];
		private $un_chars=[];
		private $base32_char_list = [
			'=' => 0b00000,
			'A' => 0b00000,
			'B' => 0b00001,
			'C' => 0b00010,
			'D' => 0b00011,
			'E' => 0b00100,
			'F' => 0b00101,
			'G' => 0b00110,
			'H' => 0b00111,
			'I' => 0b01000,
			'J' => 0b01001,
			'K' => 0b01010,
			'L' => 0b01011,
			'M' => 0b01100,
			'N' => 0b01101,
			'O' => 0b01110,
			'P' => 0b01111,
			'Q' => 0b10000,
			'R' => 0b10001,
			'S' => 0b10010,
			'T' => 0b10011,
			'U' => 0b10100,
			'V' => 0b10101,
			'W' => 0b10110,
			'X' => 0b10111,
			'Y' => 0b11000,
			'Z' => 0b11001,
			'2' => 0b11010,
			'3' => 0b11011,
			'4' => 0b11100,
			'5' => 0b11101,
			'6' => 0b11110,
			'7' => 0b11111,
		];
		function __construct() {
			$this->unchar_len=strlen($this->unchar);
			$this->base32_len=strlen($this->base32);
			$this->session=new SESSION();
		}
		private function gen_ramdom_user_name()
		{
			$un_chars_org=[];
			for($i=0;$i<$this->unlen;$i++)$this->un_chars_org[$i]=rand(0, $this->unchar_len-1);
			$this->un_chars=$this->un_chars_org;
		}
		private function next_user_name()
		{
			$shift=0;
			$this->un_chars[$this->unlen-1]++;
			for($i=$this->unlen-1;$i>=0;$i--)
			{
				$this->un_chars[$i]+=$shift;
				$shift=0;
				$shift=floor($this->un_chars[$i]/$this->unchar_len);
				$this->un_chars[$i]=$this->un_chars[$i]%$this->unchar_len;
			}
		}
		private function return_user_name()
		{
			$un="";
			for($i=0;$i<$this->unlen;$i++)$un.=$this->unchar[$this->un_chars[$i]];
			return $un;
		}
		
		private function gen_secret()
		{
			$s="";
			for ($i = 0; $i < 16; $i++) $s .= $this->base32[rand(0, $this->base32_len - 1)];
			return $s;
		}
		private function base32_decode($input)
		{
			if(!preg_match("^[A-Z2-7]+[=]{0,7}^", $input))return -1;
			if($input=="") return "";
			$in_len=strlen($input);
			if($in_len%8!==0) return -2;
			$bin=$this->base32_char_list[$input[0]];
			for($i=1;$i<$in_len;$i++)
			{
				$bin=$bin<<5;
				$bin|=$this->base32_char_list[$input[$i]];
			}
			return pack('H*',base_convert($bin, 10, 16));
		}
		private function genHOTP($key, $counter) {//rfc4226
			$arr="";$hmac_result=[];
			for($i=0;$i<8;$i++)$arr.=pack('C', $counter>>((7-$i)*8));
			$hash = hash_hmac('sha1', $arr, $key, true);
			for($i=0;$i<20;$i++)$hmac_result[$i]=ord($hash[$i]);
			$offset = $hmac_result[19] & 0xf;
			$bin_code = ($hmac_result[$offset] & 0x7f) << 24
				| ($hmac_result[$offset+1] & 0xff) << 16
				| ($hmac_result[$offset+2] & 0xff) << 8
				| ($hmac_result[$offset+3] & 0xff);
			return str_pad($bin_code%1000000, 6, "0", STR_PAD_LEFT);
		}
		
		public function create_user()
		{
			$this->gen_ramdom_user_name();
			return array("name" => $this->return_user_name(), "key" => $this->gen_secret());
		}
	}
?>